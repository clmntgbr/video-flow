#!/bin/bash
# scripts/setup-env.sh

if [ ! -f api/.env.local ]; then
    cp api/.env.dist api/.env.local
fi

if [ ! -f api/.env ]; then
    cp services/sound-extractor/.env.dist services/sound-extractor/.env
fi

# Injecter les variables du .env racine dans le .env de Symfony
set -a
source .env
envsubst < api/.env.dist > api/.env.local
envsubst < services/sound-extractor/src/.env.dist > services/sound-extractor/src/.env
set +a
