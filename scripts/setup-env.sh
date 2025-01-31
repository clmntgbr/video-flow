#!/bin/bash
# scripts/setup-env.sh

if [ ! -f .env ]; then
    cp .env.dist .env
fi

if [ ! -f api/.env.local ]; then
    cp api/.env.dist api/.env.local
fi

# Injecter les variables du .env racine dans le .env de Symfony
set -a
source .env
envsubst < api/.env.dist > api/.env.local
set +a
