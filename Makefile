#!/usr/bin/env bash

include .env
export $(shell sed 's/=.*//' .env)

DOCKER_COMPOSE = docker compose -p $(PROJECT_NAME)

CONTAINER_PHP := $(shell docker container ls -f "name=$(PROJECT_NAME)-php" -q)
CONTAINER_DB := $(shell docker container ls -f "name=$(PROJECT_NAME)-database" -q)

PHP := docker exec -ti $(CONTAINER_PHP)
DATABASE := docker exec -ti $(CONTAINER_DB)

## Kill all containers
kill:
	@$(DOCKER_COMPOSE) kill $(CONTAINER) || true

## Build containers
build:
	@$(DOCKER_COMPOSE) build --pull --no-cache

## Start containers
start:
	@$(DOCKER_COMPOSE) up --pull always -d --wait

## Stop containers
stop:
	@$(DOCKER_COMPOSE) down --remove-orphans

restart: stop start

## Entering php shell
php:
	@$(DOCKER_COMPOSE) exec php sh

## Entering database shell
database:
	@$(DOCKER_COMPOSE) exec database sh

dotenv:
	$(PHP) php bin/console debug:dotenv

setup-env:
	./scripts/setup-env.sh
