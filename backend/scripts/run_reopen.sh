#!/bin/bash

# Script wrapper per riapertura massiva ristoranti OPPLA
# Esegue lo script Python in background con nohup

HOLIDAY_IDS="$1"
LOG_FILE="/var/www/onemanager/backend/storage/logs/reopen_$(date +%s).log"

# Vai alla root del progetto
cd /var/www/onemanager

# Esegui lo script Python in background
TZ=Europe/Rome nohup python3 backend/scripts/reopen_restaurants.py \
  --holiday-ids "$HOLIDAY_IDS" \
  > "$LOG_FILE" 2>&1 &

# Stampa il PID del processo in background
echo $!
