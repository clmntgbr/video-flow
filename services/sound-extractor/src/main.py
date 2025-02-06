import json
import os
import sys
import ffmpeg
from flask import Flask
from celery import Celery
from kombu import Queue
import boto3
from dotenv import load_dotenv

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
    localFilePath = f"/tmp/{protoMediaPod.mediaPod.originalVideo.name}"

    if not downloadFromS3(key, localFilePath):
        return False

    audioPath = os.path.splitext(localFilePath)[0] + ".mp3"

    if not extractSound(localFilePath, audioPath):
        return False
    
    key = f"{protoMediaPod.mediaPod.userUuid}/{protoMediaPod.mediaPod.uuid}/{os.path.basename(audioPath)}"
    if not uploadToS3(key, audioPath):
        return False
    
    deleteFile(localFilePath)
    deleteFile(audioPath)
    
    return True

def extractSound(file: str, audioPath: str) -> bool:
    try:
        ffmpeg.input(file).output(f"{audioPath}").run()
        print(f"audio successfully extracted: {audioPath}")
        return True
    except Exception as e:
        print(f"error extracting audio: {e}")
        return False

def deleteFile(filePath: str) -> bool:
    try:
        os.remove(filePath)
        print(f"file successfully deleted: {filePath}")
        return True
    except Exception as e:
        print(f"error deleting file: {e}")
        return False
    
def downloadFromS3(key: str, localFilePath: str) -> bool:
    try:
        s3Client.download_file(S3_BUCKET_NAME, key, localFilePath)
        print(f"file successfully downloaded: {localFilePath}")
        return True
    except Exception as e:
        print(f"error downloading file: {e}")
        return False


def uploadToS3(key: str, localFilePath: str) -> bool:
    try:
        s3Client.upload_file(localFilePath, S3_BUCKET_NAME, key)
        print(f"file successfully uploaded: {localFilePath}")
        return True
    except Exception as e:
        print(f"error uploading file: {e}")
        return False

def jsonToProtobuf(json_str: str) -> SubtitleGeneratorApi:
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
    
    message = SubtitleGeneratorApi()
    message.mediaPod.CopyFrom(media_pod)
    
    return message
