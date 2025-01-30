#!/usr/bin/env bash

## Composer install
install:
	composer install

## Composer update
update:
	composer update

fabric: 
	php bin/console messenger:setup-transports

db: 
	php bin/console doctrine:database:drop -f
	php bin/console doctrine:database:create
	php bin/console doctrine:schema:update -f
	php bin/console hautelook:fixtures:load -n

fixtures:
	php bin/console hautelook:fixtures:load -n
