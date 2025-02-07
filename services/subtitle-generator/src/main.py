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
        'tasks.process_message': {'queue': 'api_subtitle_generator'}
    },
    'task_queues': [
        Queue('api_subtitle_generator', routing_key='api_subtitle_generator')
    ],
})

@celery.task(name='tasks.process_message', queue='api_subtitle_generator')
def process_message(message):
    print(message)
