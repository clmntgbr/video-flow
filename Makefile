#!/usr/bin/env bash

include .env
export $(shell sed 's/=.*//' .env)

DOCKER_COMPOSE = docker compose -p $(PROJECT_NAME)

CONTAINER_PHP := $(shell docker container ls -f "name=$(PROJECT_NAME)-php" -q)

PHP := docker exec -ti $(CONTAINER_PHP)
PHP_SH := docker exec -ti $(CONTAINER_PHP) sh -c

protobuf:
	cp video-flow-protobuf/Message.proto video-flow-api
	$(PHP_SH) "find /app/src/Protobuf -mindepth 1 ! -name '.gitkeep' -delete"
	$(PHP) protoc --proto_path=/app --php_out=src/Protobuf /app/Message.proto
	$(PHP_SH) "mv /app/src/Protobuf/App/Protobuf/* /app/src/Protobuf"
	$(PHP_SH) "rm -r /app/src/Protobuf/App"
	$(PHP_SH) "rm -r /app/Message.proto"

start:
	cd video-flow-api && $(DOCKER_COMPOSE) up -d && cd ..

php:
	$(DOCKER_COMPOSE) exec php sh
