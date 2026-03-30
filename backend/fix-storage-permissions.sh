#!/bin/bash

###########################################
# FIX STORAGE PERMISSIONS & DIRECTORIES
# Fix per OAuth callback crash
###########################################

echo "🔧 Fixing storage directories and permissions..."

# Ensure storage directories exist
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/app/public

# Set correct permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Set ownership (adjust based on your web server user)
# For Apache (Ubuntu/Debian):
# chown -R www-data:www-data storage bootstrap/cache

# For Nginx (Ubuntu/Debian):
# chown -R www-data:www-data storage bootstrap/cache

# For Nginx (CentOS/RHEL):
# chown -R nginx:nginx storage bootstrap/cache

echo "Storage directories created and permissions set"
echo ""
echo "⚠️  IMPORTANT: Set correct ownership based on your web server:"
echo "   Apache: sudo chown -R www-data:www-data storage bootstrap/cache"
echo "   Nginx:  sudo chown -R www-data:www-data storage bootstrap/cache"
echo "   (or nginx:nginx on CentOS/RHEL)"
echo ""
echo "🧪 Testing log write..."
php artisan tinker --execute="Log::info('Storage test successful');"
echo ""
echo "Done! Check storage/logs/laravel.log for test message"
