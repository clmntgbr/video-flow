import base64
import json
import time
import os
from flask import Flask, jsonify
from kombu.serialization import register
from celery import Celery

app = Flask(__name__)
app.config['CELERY_BROKER_URL'] = os.environ.get('CELERY_BROKER_URL', 'amqp://rabbitmq:rabbitmq@rabbitmq:5672/rabbitmq')

celery = Celery(
    app.name,
    broker=app.config['CELERY_BROKER_URL']
)

celery.conf.update({
    'broker_connection_retry_on_startup': True,
    'task_queues': {
        'api_sound_extractor': {
            'exchange': 'messages',
            'routing_key': 'api_sound_extractor',
        },
    },
    'accept_content': ['application/x-protobuf'],
})

@celery.task(name='api_sound_extractor', queue='api_sound_extractor')
def process_message(message):
    print("***************************")

if __name__ == 'main':
    app.run(host='0.0.0.0', debug=True)
