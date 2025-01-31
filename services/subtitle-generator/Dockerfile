FROM python:3.10-slim

WORKDIR /app

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt \
    && pip install watchdog flask-debug

COPY . .

ENV FLASK_APP=src/main.py
ENV FLASK_ENV=development
ENV FLASK_DEBUG=1

CMD ["flask", "run", "--host=0.0.0.0", "--port=5000", "--reload"]
