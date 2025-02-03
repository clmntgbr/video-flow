#!/usr/bin/env bash

include .env
export $(shell sed 's/=.*//' .env)

DOCKER_COMPOSE = docker compose -p $(PROJECT_NAME)

CONTAINER_PHP := $(shell docker container ls -f "name=$(PROJECT_NAME)-php" -q)
CONTAINER_DB := $(shell docker container ls -f "name=$(PROJECT_NAME)-database" -q)
CONTAINER_QA := $(shell docker container ls -f "name=$(PROJECT_NAME)-qa" -q)

PHP := docker exec -ti $(CONTAINER_PHP)
QA := docker exec -ti $(CONTAINER_QA)
DATABASE := docker exec -ti $(CONTAINER_DB)

## Kill all containers
kill:
	@$(DOCKER_COMPOSE) kill $(CONTAINER) || true

## Build containers
build:
	@$(DOCKER_COMPOSE) build --pull --no-cache

init:
	setup-env npm jwt db fabric proto

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
	$(PHP) protoc --proto_path=protobuf --php_out=src/Protobuf protobuf/*.proto
