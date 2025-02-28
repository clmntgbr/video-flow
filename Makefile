#!/usr/bin/env bash

include .env
export $(shell sed 's/=.*//' .env)

DOCKER_COMPOSE = docker compose -p $(BASE_PROJECT_NAME)

CONTAINER_PHP := $(shell docker container ls -f "name=$(BASE_PROJECT_NAME)-php" -q)
CONTAINER_SE := $(shell docker container ls -f "name=$(BASE_PROJECT_NAME)-sound-extractor" -q)
CONTAINER_SG := $(shell docker container ls -f "name=$(BASE_PROJECT_NAME)-subtitle-generator" -q)
CONTAINER_SM := $(shell docker container ls -f "name=$(BASE_PROJECT_NAME)-subtitle-merger" -q)
CONTAINER_ST := $(shell docker container ls -f "name=$(BASE_PROJECT_NAME)-subtitle-transformer" -q)
CONTAINER_VF := $(shell docker container ls -f "name=$(BASE_PROJECT_NAME)-video-formatter" -q)
CONTAINER_SI := $(shell docker container ls -f "name=$(BASE_PROJECT_NAME)-subtitle-incrustator" -q)

PHP := docker exec -ti $(CONTAINER_PHP)
PHP_SH := docker exec -ti $(CONTAINER_PHP) sh -c
SE := docker exec -ti $(CONTAINER_SE)
SG := docker exec -ti $(CONTAINER_SG)
SM := docker exec -ti $(CONTAINER_SM)
ST := docker exec -ti $(CONTAINER_ST)
VF := docker exec -ti $(CONTAINER_VF)
SI := docker exec -ti $(CONTAINER_SI)

protobuf:
	cp video-flow-protobuf/Message.proto video-flow-api
	cp video-flow-protobuf/Message.proto video-flow-sound-extractor
	cp video-flow-protobuf/Message.proto video-flow-subtitle-generator
	cp video-flow-protobuf/Message.proto video-flow-subtitle-merger
	cp video-flow-protobuf/Message.proto video-flow-subtitle-transformer
	cp video-flow-protobuf/Message.proto video-flow-video-formatter
	cp video-flow-protobuf/Message.proto video-flow-subtitle-incrustator
	$(PHP_SH) "find /app/src/Protobuf -mindepth 1 ! -name '.gitkeep' -delete"
	$(PHP) protoc --proto_path=/app --php_out=src/Protobuf /app/Message.proto
	$(SE) protoc --proto_path=/app --python_out=src/Protobuf /app/Message.proto
	$(SG) protoc --proto_path=/app --python_out=src/Protobuf /app/Message.proto
	$(SM) protoc --proto_path=/app --python_out=src/Protobuf /app/Message.proto
	$(ST) protoc --proto_path=/app --python_out=src/Protobuf /app/Message.proto
	$(SI) protoc --proto_path=/app --python_out=src/Protobuf /app/Message.proto
	$(VF) protoc --proto_path=/app --python_out=src/Protobuf /app/Message.proto
	$(PHP_SH) "mv /app/src/Protobuf/App/Protobuf/* /app/src/Protobuf"
	$(PHP_SH) "rm -r /app/src/Protobuf/App"
	$(PHP_SH) "rm -r /app/Message.proto"
	rm -r video-flow-sound-extractor/Message.proto
	rm -r video-flow-subtitle-generator/Message.proto
	rm -r video-flow-subtitle-merger/Message.proto
	rm -r video-flow-subtitle-transformer/Message.proto
	rm -r video-flow-video-formatter/Message.proto
	rm -r video-flow-subtitle-incrustator/Message.proto

start:
	cd video-flow-api && $(DOCKER_COMPOSE) up -d && cd ..
	cd video-flow-sound-extractor && $(DOCKER_COMPOSE) up -d && cd ..
	cd video-flow-subtitle-generator && $(DOCKER_COMPOSE) up -d && cd ..
	cd video-flow-subtitle-merger && $(DOCKER_COMPOSE) up -d && cd ..
	cd video-flow-subtitle-transformer && $(DOCKER_COMPOSE) up -d && cd ..
	cd video-flow-video-formatter && $(DOCKER_COMPOSE) up -d && cd ..
	cd video-flow-subtitle-incrustator && $(DOCKER_COMPOSE) up -d && cd ..

stop:
	cd video-flow-api && $(DOCKER_COMPOSE) down --remove-orphans && cd ..
	cd video-flow-sound-extractor && $(DOCKER_COMPOSE) down --remove-orphans && cd ..
	cd video-flow-subtitle-generator && $(DOCKER_COMPOSE) down --remove-orphans && cd ..
	cd video-flow-subtitle-merger && $(DOCKER_COMPOSE) down --remove-orphans && cd ..
	cd video-flow-subtitle-transformer && $(DOCKER_COMPOSE) down --remove-orphans && cd ..
	cd video-flow-video-formatter && $(DOCKER_COMPOSE) down --remove-orphans && cd ..
	cd video-flow-subtitle-incrustator && $(DOCKER_COMPOSE) down --remove-orphans && cd ..

build: 
	cd video-flow-api && $(DOCKER_COMPOSE) build --pull --no-cache && cd ..
	cd video-flow-sound-extractor && $(DOCKER_COMPOSE) build --pull --no-cache && cd ..
	cd video-flow-subtitle-generator && $(DOCKER_COMPOSE) build --pull --no-cache && cd ..
	cd video-flow-subtitle-merger && $(DOCKER_COMPOSE) build --pull --no-cache && cd ..
	cd video-flow-subtitle-transformer && $(DOCKER_COMPOSE) build --pull --no-cache && cd ..
	cd video-flow-video-formatter && $(DOCKER_COMPOSE) build --pull --no-cache && cd ..
	cd video-flow-subtitle-incrustator && $(DOCKER_COMPOSE) build --pull --no-cache && cd ..

fix:
	cd video-flow-api && make php-cs-fixer && cd ..
	cd video-flow-sound-extractor && make fix && cd ..
	cd video-flow-subtitle-generator && make fix && cd ..
	cd video-flow-subtitle-merger && make fix && cd ..
	cd video-flow-subtitle-transformer && make fix && cd ..
	cd video-flow-video-formatter && make fix && cd ..
	cd video-flow-subtitle-incrustator && make fix && cd ..
setupenv:
	bash setup-env.sh
