#!/bin/bash

# Script wrapper per chiusura massiva ristoranti OPPLA
# Esegue lo script Python in background con nohup

START_DATE="$1"
END_DATE="$2"
REASON="${3:-Chiusura programmata}"
BATCH_ID="$4"
API_URL="$5"
LOG_FILE="/var/www/onemanager/backend/storage/logs/closure_$(date +%s).log"

# Vai alla root del progetto
cd /var/www/onemanager

# Esegui lo script Python in background
TZ=Europe/Rome nohup python3 backend/scripts/close_restaurants.py \
  --start "$START_DATE" \
  --end "$END_DATE" \
  --reason "$REASON" \
  --batch-id "$BATCH_ID" \
  --api-url "$API_URL" \
  --headless \
  > "$LOG_FILE" 2>&1 &

# Stampa il PID del processo in background
echo $!
