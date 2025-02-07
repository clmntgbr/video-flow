import json
import os
import sys
import whisper
from flask import Flask
from celery import Celery
from kombu import Queue
import boto3
from dotenv import load_dotenv
from google.protobuf.json_format import MessageToJson

sys.path.append('/app/src')
from Protobuf.Message_pb2 import MediaPod, Video, SubtitleGeneratorApi

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
        'tasks.process_message': {'queue': 'api_subtitle_generator'}
    },
    'task_queues': [
        Queue('api_subtitle_generator', routing_key='api_subtitle_generator')
    ],
})

@celery.task(name='tasks.process_message', queue='api_subtitle_generator')
def process_message(message):
    print(message)
    protoMediaPod = jsonToProtobuf(message)
    print(protoMediaPod)

    return True
    
    key = f"{protoMediaPod.mediaPod.userUuid}/{protoMediaPod.mediaPod.uuid}/{protoMediaPod.mediaPod.originalVideo.subtitleName}"
    s3FilePath = f"/tmp/{protoMediaPod.mediaPod.originalVideo.subtitleName}"
    srtFilePath = os.path.splitext(s3FilePath)[0] + ".srt"

    if not downloadFromS3(key, s3FilePath):
        return False
    
    print(f"transcription in pending")
    model = whisper.load_model("small")
    result = model.transcribe(s3FilePath, fp16=False)
    print(f"file successfully transcribed")

    with open(srtFilePath, "w", encoding="utf-8") as f:
        for i, segment in enumerate(result["segments"]):
            start = segment["start"]
            end = segment["end"]
            text = segment["text"]

            start_time = f"{int(start // 3600):02}:{int((start % 3600) // 60):02}:{int(start % 60):02},{int((start % 1) * 1000):03}"
            end_time = f"{int(end // 3600):02}:{int((end % 3600) // 60):02}:{int(end % 60):02},{int((end % 1) * 1000):03}"

            f.write(f"{i + 1}\n{start_time} --> {end_time}\n{text.strip()}\n\n")
    
    print(f"srt file successfully generated")

    key = f"{protoMediaPod.mediaPod.userUuid}/{protoMediaPod.mediaPod.uuid}/{os.path.basename(srtFilePath)}"
    if not uploadToS3(key, srtFilePath):
        return False

    print("Sous-titres générés : subtitles.srt")
    
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
    video.subtitles.extend(media_pod_data["originalVideo"]["subtitles"])
    
    media_pod = MediaPod()
    media_pod.uuid = media_pod_data["uuid"]
    media_pod.userUuid = media_pod_data["userUuid"]
    media_pod.status = media_pod_data["status"]
    media_pod.originalVideo.CopyFrom(video)
    
    message = SubtitleGeneratorApi()
    message.mediaPod.CopyFrom(media_pod)
    
    return message
