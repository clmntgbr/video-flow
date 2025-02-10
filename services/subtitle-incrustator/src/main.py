import json
import os
import sys
import pika
import re
from flask import Flask
from celery import Celery
from kombu import Queue
import boto3
from dotenv import load_dotenv
from google.protobuf.json_format import MessageToJson

sys.path.append('/app/src')
from Protobuf.Message_pb2 import MediaPod, Video, SubtitleIncrustatorApi

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

    try:
        print(protoMediaPod)
    except Exception as e:
        protoMediaPod.mediaPod.status = 'subtitle_incrustator_error'
        if not sendMessageOnRabbitMQ(protoMediaPod):
            return False

    return True

def sendMessageOnRabbitMQ(protoMediaPod: SubtitleIncrustatorApi) -> bool:
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
            headers={"type": "App\\Protobuf\\SubtitleIncrustatorApi"}
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

def jsonToProtobuf(json_str: str) -> SubtitleIncrustatorApi:
    data = json.loads(json_str)

    media_pod_data = data["mediaPod"]
    
    video = Video()
    video.name = media_pod_data["originalVideo"]["name"]
    video.mimeType = media_pod_data["originalVideo"]["mimeType"]
    video.size = int(media_pod_data["originalVideo"]["size"])
    video.audios.extend(media_pod_data["originalVideo"]["audios"])
    video.subtitle = media_pod_data["originalVideo"]["subtitle"]
    video.subtitles.extend(media_pod_data["originalVideo"]["subtitles"])
    
    media_pod = MediaPod()
    media_pod.uuid = media_pod_data["uuid"]
    media_pod.userUuid = media_pod_data["userUuid"]
    media_pod.status = media_pod_data["status"]
    media_pod.originalVideo.CopyFrom(video)
    
    message = SubtitleIncrustatorApi()
    message.mediaPod.CopyFrom(media_pod)
    
    return message
