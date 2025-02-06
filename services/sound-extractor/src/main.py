import os
from flask import Flask, jsonify
from celery import Celery
from kombu import Queue

app = Flask(__name__)
app.config['CELERY_RABBITMQ_URL'] = os.environ.get('CELERY_RABBITMQ_URL')

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

# Définition de la tâche Celery
@celery.task(name='tasks.process_message', queue='api_sound_extractor')
def process_message(message):
    print("✅ Received message:", message)
    return {"status": "processed", "message": message}
