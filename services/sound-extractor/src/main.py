import json
import os
import re
import sys
import ffmpeg
import pika
from flask import Flask
from celery import Celery
from kombu import Queue
import boto3
from dotenv import load_dotenv
from google.protobuf.json_format import MessageToJson
from pydub import AudioSegment

sys.path.append('/app/src')
from Protobuf.Message_pb2 import MediaPod, Video, SoundExtractorApi

load_dotenv()

app = Flask(__name__)
app.config['CELERY_RABBITMQ_URL'] = os.getenv("CELERY_RABBITMQ_URL")

S3_ACCESS_KEY = os.getenv("S3_ACCESS_KEY")
S3_SECRET_KEY = os.getenv("S3_SECRET_KEY")
S3_ENDPOINT = os.getenv("S3_ENDPOINT")
S3_BUCKET_NAME = os.getenv("S3_BUCKET_NAME")
S3_REGION = os.getenv("S3_REGION")

s3Client = boto3.client(
    's3',
    aws_access_key_id=S3_ACCESS_KEY,
    aws_secret_access_key=S3_SECRET_KEY,
    endpoint_url=S3_ENDPOINT,
    region_name=S3_REGION
)

celery = Celery(
    'tasks',
    broker=app.config['CELERY_RABBITMQ_URL']
)

celery.conf.update({
    'task_serializer': 'json',
    'accept_content': ['json'],
    'broker_connection_retry_on_startup': True,
    'task_routes': {
        'tasks.process_message': {'queue': 'api_sound_extractor'}
    },
    'task_queues': [
        Queue('api_sound_extractor', routing_key='api_sound_extractor')
    ],
})

@celery.task(name='tasks.process_message', queue='api_sound_extractor')
def process_message(message):
    protoMediaPod = jsonToProtobuf(message)

    key = f"{protoMediaPod.mediaPod.userUuid}/{protoMediaPod.mediaPod.uuid}/{protoMediaPod.mediaPod.originalVideo.name}"
    s3FilePath = f"/tmp/{protoMediaPod.mediaPod.originalVideo.name}"
    uuid = os.path.splitext(os.path.basename(s3FilePath))[0]

    if not downloadFromS3(key, s3FilePath):
        return False

    audioFilePath = uuid + ".mp3"

    if not extractSound(s3FilePath, f"/tmp/{audioFilePath}"):
        return False
    
    audioFilePath = convertToWav(f"/tmp/{audioFilePath}")
    
    chunks = chunkWav(audioFilePath, uuid)

    for chunk in chunks:
        key = f"{protoMediaPod.mediaPod.userUuid}/{protoMediaPod.mediaPod.uuid}/audios/{chunk}"
        if not uploadToS3(key, f"/tmp/{chunk}"):
            return False
        deleteFile(f"/tmp/{chunk}")
        
    deleteFile(s3FilePath)
    deleteFile(audioFilePath)

    resultsSorted = sorted(chunks, key=extractChunkNumber)
    protoMediaPod.mediaPod.originalVideo.audios.extend(chunks)
    protoMediaPod.mediaPod.status = 'sound_extractor_complete'
    
    if not sendMessageOnRabbitMQ(protoMediaPod):
        return False
    
    return True

def extractChunkNumber(item):
    match = re.search(r'_(\d+)\.wav$', item[0])
    return int(match.group(1)) if match else float('inf')

def chunkWav(audioFilePath: str, uuid: str) -> list[str]: 
    audio = AudioSegment.from_mp3(audioFilePath)
    segmentDuration = 5 * 60 * 1000
    chunkFilenames = []

    chunks = [audio[i:i+segmentDuration] for i in range(0, len(audio), segmentDuration)]
    for idx, chunk in enumerate(chunks):
        chunk.export(f"/tmp/{uuid}_{idx+1}.wav", format="wav")
        chunkFilenames.append(f"{uuid}_{idx+1}.wav")

    return chunkFilenames

def sendMessageOnRabbitMQ(protoMediaPod: SoundExtractorApi) -> bool:
    message = {
        "task": "tasks.process_message",
        "args": [MessageToJson(protoMediaPod)],
        "queue": 'sound_extractor_api'
    }

    parameters = pika.URLParameters(os.getenv("CELERY_RABBITMQ_URL"))
    connection = pika.BlockingConnection(parameters)
    channel = connection.channel()
    
    channel.queue_declare(queue='sound_extractor_api', durable=True)

    channel.basic_publish(
        exchange='messages',
        routing_key='sound_extractor_api',
        body=json.dumps(message),
        properties=pika.BasicProperties(
            delivery_mode=2,
            content_type="application/json",
            headers={"type": "App\\Protobuf\\SoundExtractorApi"}
        )
    )

    connection.close()

def extractSound(file: str, audioFilePath: str) -> bool:
    try:
        ffmpeg.input(file).output(f"{audioFilePath}").run()
        print(f"audio successfully extracted: {audioFilePath}")
        return True
    except Exception as e:
        print(f"error extracting audio: {e}")
        return False

def convertToWav(audioFilePath) -> str:
    audio = AudioSegment.from_mp3(audioFilePath)
    wav_path = audioFilePath.replace(".mp3", ".wav")
    audio.export(wav_path, format="wav", parameters=["-ac", "1", "-ar", "16000"]) 
    return wav_path

def deleteFile(filePath: str) -> bool:
    try:
        os.remove(filePath)
        print(f"file successfully deleted: {filePath}")
        return True
    except Exception as e:
        print(f"error deleting file: {e}")
        return False
    
def downloadFromS3(key: str, s3FilePath: str) -> bool:
    try:
        s3Client.download_file(S3_BUCKET_NAME, key, s3FilePath)
        print(f"file successfully downloaded: {s3FilePath}")
        return True
    except Exception as e:
        print(f"error downloading file: {e}")
        return False

def uploadToS3(key: str, s3FilePath: str) -> bool:
    try:
        s3Client.upload_file(s3FilePath, S3_BUCKET_NAME, key)
        print(f"file successfully uploaded: {s3FilePath}")
        return True
    except Exception as e:
        print(f"error uploading file: {e}")
        return False

def jsonToProtobuf(json_str: str) -> SoundExtractorApi:
    data = json.loads(json_str)
    media_pod_data = data["mediaPod"]
    
    video = Video()
    video.name = media_pod_data["originalVideo"]["name"]
    video.mimeType = media_pod_data["originalVideo"]["mimeType"]
    video.size = int(media_pod_data["originalVideo"]["size"])
    
    media_pod = MediaPod()
    media_pod.uuid = media_pod_data["uuid"]
    media_pod.userUuid = media_pod_data["userUuid"]
    media_pod.originalVideo.CopyFrom(video)
    
    message = SoundExtractorApi()
    message.mediaPod.CopyFrom(media_pod)
    
    return message
