import json
import os
import sys
import pika
from flask import Flask
from celery import Celery
from kombu import Queue
import boto3
from dotenv import load_dotenv
from google.protobuf.json_format import MessageToJson

sys.path.append('/app/src')
from Protobuf.Message_pb2 import MediaPod, Video, SubtitleMergerApi

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
        'tasks.process_message': {'queue': 'api_subtitle_merger'}
    },
    'task_queues': [
        Queue('api_subtitle_merger', routing_key='api_subtitle_merger')
    ],
})

@celery.task(name='tasks.process_message', queue='api_subtitle_merger')
def process_message(message):
    protoMediaPod = jsonToProtobuf(message)
    print(protoMediaPod)

    return True

def sendMessageOnRabbitMQ(protoMediaPod: SubtitleMergerApi) -> bool:
    message = {
        "task": "tasks.process_message",
        "args": [MessageToJson(protoMediaPod)],
        "queue": 'subtitle_merger_api'
    }

    parameters = pika.URLParameters(os.getenv("CELERY_RABBITMQ_URL"))
    connection = pika.BlockingConnection(parameters)
    channel = connection.channel()
    
    channel.queue_declare(queue='subtitle_merger_api', durable=True)

    channel.basic_publish(
        exchange='messages',
        routing_key='subtitle_merger_api',
        body=json.dumps(message),
        properties=pika.BasicProperties(
            delivery_mode=2,
            content_type="application/json",
            headers={"type": "App\\Protobuf\\SubtitleMergerApi"}
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

def jsonToProtobuf(json_str: str) -> SubtitleMergerApi:
    data = json.loads(json_str)

    media_pod_data = data["mediaPod"]
    
    video = Video()
    video.name = media_pod_data["originalVideo"]["name"]
    video.mimeType = media_pod_data["originalVideo"]["mimeType"]
    video.size = int(media_pod_data["originalVideo"]["size"])
    video.audios.extend(media_pod_data["originalVideo"]["audios"])
    video.subtitles.extend(media_pod_data["originalVideo"]["subtitles"])
    
    media_pod = MediaPod()
    media_pod.uuid = media_pod_data["uuid"]
    media_pod.userUuid = media_pod_data["userUuid"]
    media_pod.status = media_pod_data["status"]
    media_pod.originalVideo.CopyFrom(video)
    
    message = SubtitleMergerApi()
    message.mediaPod.CopyFrom(media_pod)
    
    return message
