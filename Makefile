#!/usr/bin/env bash

include .env
export $(shell sed 's/=.*//' .env)

DOCKER_COMPOSE = docker compose -p $(PROJECT_NAME)

CONTAINER_PHP := $(shell docker container ls -f "name=$(PROJECT_NAME)-php" -q)
CONTAINER_DB := $(shell docker container ls -f "name=$(PROJECT_NAME)-database" -q)
CONTAINER_QA := $(shell docker container ls -f "name=$(PROJECT_NAME)-qa" -q)
CONTAINER_SG := $(shell docker container ls -f "name=$(PROJECT_NAME)-subtitle-generator" -q)
CONTAINER_SE := $(shell docker container ls -f "name=$(PROJECT_NAME)-sound-extractor" -q)

PHP := docker exec -ti $(CONTAINER_PHP)
PHP_SH := docker exec -ti $(CONTAINER_PHP) sh -c
SG := docker exec -ti $(CONTAINER_SG)
SE := docker exec -ti $(CONTAINER_SE)
QA := docker exec -ti $(CONTAINER_QA)
DATABASE := docker exec -ti $(CONTAINER_DB)

## Kill all containers
kill:
	@$(DOCKER_COMPOSE) kill $(CONTAINER) || true

## Build containers
build:
	@$(DOCKER_COMPOSE) build --pull --no-cache

init: npm jwt db fabric proto

## Start containers
start:
	@$(DOCKER_COMPOSE) up --pull always -d --wait

## Stop containers
stop:
	@$(DOCKER_COMPOSE) down --remove-orphans

restart: stop start

npm: 
	$(PHP) npm install
	$(PHP) npm run build

jwt: 
	$(PHP) php bin/console lexik:jwt:generate-keypair --skip-if-exists

## Entering php shell
php:
	@$(DOCKER_COMPOSE) exec php sh

## Entering php shell
subtitle-generator:
	@$(DOCKER_COMPOSE) exec subtitle-generator sh

## Entering database shell
database:
	@$(DOCKER_COMPOSE) exec database sh

dotenv:
	$(PHP) php bin/console debug:dotenv

setup-env:
	./scripts/setup-env.sh

fabric: 
	$(PHP) php bin/console messenger:setup-transports

command:
	$(PHP) php bin/console $(filter-out $@,$(MAKECMDGOALS))

db: 
	$(PHP) php bin/console doctrine:database:drop -f
	$(PHP) php bin/console doctrine:database:create
	$(PHP) php bin/console doctrine:schema:update -f
	$(PHP) php bin/console hautelook:fixtures:load -n
	
consume-sound-extractor:
	$(PHP) php bin/console messenger:consume sound_extractor_api -vv

schema:
	$(PHP) php bin/console doctrine:schema:update -f

regenerate:
	$(PHP) php bin/console make:entity --regenerate App

fixtures:
	$(PHP) php bin/console hautelook:fixtures:load -n

php-cs-fixer:
	$(QA) ./php-cs-fixer fix src --rules=@Symfony --verbose --diff

php-stan:
	$(QA) ./vendor/bin/phpstan analyse src -l $(or $(level), 5)

proto: 
	$(PHP_SH) "rm -r /app/src/Protobuf/*"
	$(PHP) protoc --proto_path=protobuf --php_out=src/Protobuf protobuf/*.proto
	$(PHP_SH) "mv /app/src/Protobuf/App/Protobuf/* /app/src/Protobuf"
	$(PHP_SH) "rm -r /app/src/Protobuf/App"
	$(SG) protoc --proto_path=protobuf --python_out=src/Protobuf protobuf/*.proto
	$(SE) protoc --proto_path=protobuf --python_out=src/Protobuf protobuf/*.proto
