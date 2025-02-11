from functools import partial
import json
import os
import sys
import whisper
import re
import pika
import openai
from openai import OpenAI
from flask import Flask
from celery import Celery
from kombu import Queue
import boto3
from dotenv import load_dotenv
from google.protobuf.json_format import MessageToJson
from concurrent.futures import ThreadPoolExecutor
from functools import partial
import assemblyai as aai
from datetime import timedelta

sys.path.append('/app/src')
from Protobuf.Message_pb2 import MediaPod, Video, SubtitleGeneratorApi

load_dotenv()

app = Flask(__name__)
app.config['CELERY_RABBITMQ_URL'] = os.getenv("CELERY_RABBITMQ_URL")

RMQ_QUEUE_WRITE = os.getenv("RMQ_QUEUE_WRITE")
RMQ_QUEUE_READ = os.getenv("RMQ_QUEUE_READ")

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
        'tasks.process_message': {'queue': RMQ_QUEUE_READ}
    },
    'task_queues': [
        Queue(RMQ_QUEUE_READ, routing_key=RMQ_QUEUE_READ)
    ],
})

@celery.task(name='tasks.process_message', queue=RMQ_QUEUE_READ)
def process_message(message):
    protoMediaPod = jsonToProtobuf(message)

    chunks = []

    try:
        for audio in protoMediaPod.mediaPod.originalVideo.audios:
            chunks.append(audio)

        partialMultiprocess = partial(multiprocess, protoMediaPod=protoMediaPod)

        with ThreadPoolExecutor(max_workers=4) as executor:
            results = list(executor.map(partialMultiprocess, chunks))

        resultsSorted = sorted(results, key=extractChunkNumber)
        protoMediaPod.mediaPod.originalVideo.subtitles.extend(resultsSorted)
        protoMediaPod.mediaPod.status = 'subtitle_generator_complete'

        sendMessageOnRabbitMQ(protoMediaPod)
        return True
    except Exception as e:
        protoMediaPod.mediaPod.status = 'subtitle_generator_error'
        sendMessageOnRabbitMQ(protoMediaPod)
        return False

def multiprocess(chunk: str, protoMediaPod: SubtitleGeneratorApi):
    key = f"{protoMediaPod.mediaPod.userUuid}/{protoMediaPod.mediaPod.uuid}/audios/{chunk}"
    s3FilePath = f"/tmp/{chunk}"
    srtFilePath = os.path.splitext(s3FilePath)[0] + ".srt"
    
    if not downloadFromS3(key, s3FilePath):
            return False
        
    if not generateSubtitleAssemblyAI(s3FilePath, srtFilePath):
        return False
    
    key = f"{protoMediaPod.mediaPod.userUuid}/{protoMediaPod.mediaPod.uuid}/subtitles/{os.path.basename(srtFilePath)}"

    if not uploadToS3(key, srtFilePath):
            return False
    
    return os.path.basename(srtFilePath)

def extractChunkNumber(item):
    match = re.search(r'_(\d+)\.srt$', item[0])
    return int(match.group(1)) if match else float('inf')

def generateSubtitleOpenAI(s3FilePath: str, srtFilePath: str) -> bool:
    print("Uploading file for transcription...")

    client = OpenAI(api_key=os.getenv("OPEN_AI_API_KEY"))

    with open(s3FilePath, "rb") as audio_file:
        response = client.audio.transcriptions.create(
            file=audio_file,
            model="whisper-1",
            response_format="srt",
        )

    print("File successfully transcribed")

    with open(srtFilePath, "w", encoding="utf-8") as file:
        file.write(response)

    print("SRT file successfully generated")
    return True

def msToSrtTime(ms):
    td = timedelta(milliseconds=ms)
    return f"{td.seconds // 3600:02}:{(td.seconds % 3600) // 60:02}:{td.seconds % 60:02},{td.microseconds // 1000:03}"

def generateSubtitleAssemblyAI(s3FilePath: str, srtFilePath: str) -> bool:
    print("Uploading file for transcription...")

    aai.settings.api_key = os.getenv("ASSEMBLY_AI_API_KEY")
    config = aai.TranscriptionConfig(language_detection=True)
    transcriber = aai.Transcriber(config=config)

    transcript = transcriber.transcribe(s3FilePath)
    words = transcript.words

    srtContent = ""
    subIndex = 1
    currentLine = []
    startTime = words[0].start

    for i, word in enumerate(words):
        currentLine.append(word.text)
    
        if len(currentLine) >= 5 or i == len(words) - 1:
            endTime = words[i].end

            srtContent += f"{subIndex}\n"
            srtContent += f"{msToSrtTime(startTime)} --> {msToSrtTime(endTime)}\n"
            srtContent += " ".join(currentLine) + "\n\n"

            subIndex += 1
            currentLine = []
            if i < len(words) - 1:
                startTime = words[i + 1].start

    print("File successfully transcribed")

    with open(srtFilePath, "w", encoding="utf-8") as file:
        file.write(srtContent)

    print("SRT file successfully generated")
    return True

def sendMessageOnRabbitMQ(protoMediaPod: SubtitleGeneratorApi) -> bool:
    message = {
        "task": "tasks.process_message",
        "args": [MessageToJson(protoMediaPod)],
        "queue": RMQ_QUEUE_WRITE
    }

    parameters = pika.URLParameters(os.getenv("CELERY_RABBITMQ_URL"))
    connection = pika.BlockingConnection(parameters)
    channel = connection.channel()
    
    channel.queue_declare(queue=RMQ_QUEUE_WRITE, durable=True)

    channel.basic_publish(
        exchange='messages',
        routing_key=RMQ_QUEUE_WRITE,
        body=json.dumps(message),
        properties=pika.BasicProperties(
            delivery_mode=2,
            content_type="application/json",
            headers={"type": "App\\Protobuf\\SubtitleGeneratorApi"}
        )
    )

    connection.close()

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
        print(f"✅ file successfully uploaded: {s3FilePath}")
        return True
    except Exception as e:
        print(f"error uploading file: {e}")
        return False

def deleteFile(filePath: str) -> bool:
    try:
        os.remove(filePath)
        print(f"file successfully deleted: {filePath}")
        return True
    except Exception as e:
        print(f"error deleting file: {e}")
        return False

def jsonToProtobuf(json_str: str) -> SubtitleGeneratorApi:
    data = json.loads(json_str)

    media_pod_data = data["mediaPod"]
    
    video = Video()
    video.name = media_pod_data["originalVideo"]["name"]
    video.mimeType = media_pod_data["originalVideo"]["mimeType"]
    video.size = int(media_pod_data["originalVideo"]["size"])
    video.audios.extend(media_pod_data["originalVideo"]["audios"])
    
    media_pod = MediaPod()
    media_pod.uuid = media_pod_data["uuid"]
    media_pod.userUuid = media_pod_data["userUuid"]
    media_pod.status = media_pod_data["status"]
    media_pod.originalVideo.CopyFrom(video)
    
    message = SubtitleGeneratorApi()
    message.mediaPod.CopyFrom(media_pod)
    
    return message
