#!/bin/bash

# Définition des fichiers
BASE_ENV=".env.local"
API_ENV="video-flow-api/.env"
LOCAL_ENV="video-flow-api/.env.local"

# Vérification de l'existence des fichiers sources
if [ ! -f "$API_ENV" ]; then
    echo "Le fichier $API_ENV est introuvable."
    exit 1
fi

# Création/Réinitialisation du fichier .env.local
cp "$API_ENV" "$LOCAL_ENV"

# Fonction pour récupérer une variable depuis un fichier
get_env_var() {
    local file=$1
    local var_name=$2
    grep -E "^$var_name=" "$file" | tail -n 1 | cut -d'=' -f2- | sed 's/"//g'
}

# Détection de l'OS pour sed
if [[ "$(uname)" == "Darwin" ]]; then
    SED_I="sed -i ''"  # macOS
else
    SED_I="sed -i"      # Linux
fi

# Remplacement des variables dans le fichier .env.local
while IFS='=' read -r key value || [ -n "$key" ]; do
    if [[ "$value" =~ \${([A-Za-z_][A-Za-z0-9_]*)} ]]; then
        var_name="${BASH_REMATCH[1]}"
        replacement_value=$(get_env_var "$BASE_ENV" "$var_name")
        if [ -z "$replacement_value" ]; then
            replacement_value=$(get_env_var "$API_ENV" "$var_name")
        fi
        if [ -n "$replacement_value" ]; then
            if [[ "$(uname)" == "Darwin" ]]; then
                sed -i '' "s|\${$var_name}|$replacement_value|g" "$LOCAL_ENV"
            else
                sed -i "s|\${$var_name}|$replacement_value|g" "$LOCAL_ENV"
            fi
        fi
    fi
done < "$API_ENV"
