#!/bin/bash

ROOT_ENV=".env.local"
API_ENV="video-flow-api/.env.dist"
LOCAL_ENV="video-flow-api/.env"

SOUND_ENV="video-flow-sound-extractor/.env"
SOUND_LOCAL_ENV="video-flow-sound-extractor/.env.local"

SUBTITLE_GENERATOR_ENV="video-flow-subtitle-generator/.env"
SUBTITLE_GENERATOR_LOCAL_ENV="video-flow-subtitle-generator/.env.local"

SUBTITLE_MERGER_ENV="video-flow-subtitle-merger/.env"
SUBTITLE_MERGER_LOCAL_ENV="video-flow-subtitle-merger/.env.local"

SUBTITLE_TRANSFORMER_ENV="video-flow-subtitle-transformer/.env"
SUBTITLE_TRANSFORMER_LOCAL_ENV="video-flow-subtitle-transformer/.env.local"

SUBTITLE_INCRUSTATOR_ENV="video-flow-subtitle-incrustator/.env"
SUBTITLE_INCRUSTATOR_LOCAL_ENV="video-flow-subtitle-incrustator/.env.local"

VIDEO_SPLITTER_ENV="video-flow-video-splitter/.env"
VIDEO_SPLITTER_LOCAL_ENV="video-flow-video-splitter/.env.local"

VIDEO_FORMATTER_ENV="video-flow-video-formatter/.env"
VIDEO_FORMATTER_LOCAL_ENV="video-flow-video-formatter/.env.local"

VIDEO_INCRUSTATOR_ENV="video-flow-video-incrustator/.env"
VIDEO_INCRUSTATOR_LOCAL_ENV="video-flow-video-incrustator/.env.local"

# Check if source files exist
if [ ! -f "$API_ENV" ] || [ ! -f "$SOUND_ENV" ] || [ ! -f "$SUBTITLE_GENERATOR_ENV" ] || [ ! -f "$SUBTITLE_MERGER_ENV" ] || [ ! -f "$SUBTITLE_TRANSFORMER_ENV" ] || [ ! -f "$SUBTITLE_INCRUSTATOR_ENV" ] || [ ! -f "$VIDEO_SPLITTER_ENV" ] || [ ! -f "$VIDEO_FORMATTER_ENV" ] || [ ! -f "$VIDEO_INCRUSTATOR_ENV" ]; then
    echo "Source environment files not found"
    exit 1
fi

rm -r "$LOCAL_ENV" "$SOUND_LOCAL_ENV" "$SUBTITLE_GENERATOR_LOCAL_ENV" "$SUBTITLE_MERGER_LOCAL_ENV" "$SUBTITLE_TRANSFORMER_LOCAL_ENV" "$SUBTITLE_INCRUSTATOR_LOCAL_ENV" "$VIDEO_SPLITTER_LOCAL_ENV" "$VIDEO_FORMATTER_LOCAL_ENV" "$VIDEO_INCRUSTATOR_LOCAL_ENV" 2> /dev/null

# Create local copies
cp "$API_ENV" "$LOCAL_ENV"
cp "$SOUND_ENV" "$SOUND_LOCAL_ENV"
cp "$SUBTITLE_GENERATOR_ENV" "$SUBTITLE_GENERATOR_LOCAL_ENV"
cp "$SUBTITLE_MERGER_ENV" "$SUBTITLE_MERGER_LOCAL_ENV"
cp "$SUBTITLE_TRANSFORMER_ENV" "$SUBTITLE_TRANSFORMER_LOCAL_ENV"
cp "$SUBTITLE_INCRUSTATOR_ENV" "$SUBTITLE_INCRUSTATOR_LOCAL_ENV"
cp "$VIDEO_SPLITTER_ENV" "$VIDEO_SPLITTER_LOCAL_ENV"
cp "$VIDEO_FORMATTER_ENV" "$VIDEO_FORMATTER_LOCAL_ENV"
cp "$VIDEO_INCRUSTATOR_ENV" "$VIDEO_INCRUSTATOR_LOCAL_ENV"

# Improved get_env_var function that handles quoted values
get_env_var() {
    local file=$1
    local var_name=$2
    local value=$(grep -E "^${var_name}=" "$file" | tail -n 1 | cut -d'=' -f2-)
    # Remove surrounding quotes if they exist
    value="${value#\"}"
    value="${value%\"}"
    echo "$value"
}

# Set appropriate sed command based on OS
if [[ "$(uname)" == "Darwin" ]]; then
    SED_I=("sed" "-i" "")  # macOS - using array to handle arguments properly
else
    SED_I=("sed" "-i")     # Linux
fi

# Improved replace_env_vars function
replace_env_vars() {
    local source_file=$1
    local target_file=$2
    local max_iterations=5  # Increased iterations for complex nested variables
    
    for ((i=0; i<max_iterations; i++)); do
        local replaced=false
        
        # Read file line by line
        while IFS= read -r line || [ -n "$line" ]; do
            if [[ "$line" =~ .*\$\{([A-Za-z_][A-Za-z0-9_]*)\}.* ]]; then
                local var_name="${BASH_REMATCH[1]}"
                # First try ROOT_ENV, then source_file
                local replacement_value=$(get_env_var "$ROOT_ENV" "$var_name")
                if [ -z "$replacement_value" ]; then
                    replacement_value=$(get_env_var "$source_file" "$var_name")
                fi
                
                if [ -n "$replacement_value" ]; then
                    # Escape special characters in the replacement value
                    replacement_value=$(echo "$replacement_value" | sed 's/[\/&]/\\&/g')
                    "${SED_I[@]}" "s/\${$var_name}/$replacement_value/g" "$target_file"
                    replaced=true
                fi
            fi
        done < "$target_file"
        
        # Break if no replacements were made in this iteration
        if [ "$replaced" = false ]; then
            break
        fi
    done
}

# Perform replacements
replace_env_vars "$API_ENV" "$LOCAL_ENV"
replace_env_vars "$SOUND_ENV" "$SOUND_LOCAL_ENV"
replace_env_vars "$SUBTITLE_GENERATOR_ENV" "$SUBTITLE_GENERATOR_LOCAL_ENV"
replace_env_vars "$SUBTITLE_MERGER_ENV" "$SUBTITLE_MERGER_LOCAL_ENV"
replace_env_vars "$SUBTITLE_TRANSFORMER_ENV" "$SUBTITLE_TRANSFORMER_LOCAL_ENV"
replace_env_vars "$SUBTITLE_INCRUSTATOR_ENV" "$SUBTITLE_INCRUSTATOR_LOCAL_ENV"
replace_env_vars "$VIDEO_SPLITTER_ENV" "$VIDEO_SPLITTER_LOCAL_ENV"
replace_env_vars "$VIDEO_FORMATTER_ENV" "$VIDEO_FORMATTER_LOCAL_ENV"
replace_env_vars "$VIDEO_INCRUSTATOR_ENV" "$VIDEO_INCRUSTATOR_LOCAL_ENV"
