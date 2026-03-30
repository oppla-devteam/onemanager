#!/bin/bash
# Script di deploy per il backend in produzione

echo "🚀 Inizio deploy backend..."

# 1. Cache clear
echo "🧹 Pulizia cache..."
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# 2. Ottimizzazione per produzione
echo "⚙️ Ottimizzazione..."
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache

# 3. Migrazioni database
php artisan migrate --force

echo "Deploy backend completato!"
echo "📝 Ricorda di verificare:"
echo "   - Il file .env.production è corretto"
echo "   - L'estensione pdo_pgsql è installata"
echo "   - Il database PostgreSQL è accessibile"
