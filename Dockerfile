# Stage 1: Build Frontend React App
FROM node:20-alpine AS frontend-builder
WORKDIR /frontend
COPY frontend/package*.json ./
RUN npm ci
COPY frontend/ ./
RUN VITE_API_URL=/api npm run build

# Stage 2: Backend Laravel + Frontend combined
FROM php:8.4-cli
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libzip-dev libxml2-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql zip bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY backend/ .

# Rewrite web.php to serve React SPA for all routes
RUN cat > routes/web.php << 'WEBPHP'
<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
Route::get('/contracts/sign/{token}', function ($token) {
    return view('contracts.signature-page');
})->name('contracts.sign');
Route::get('/contracts/signed-success', function () {
    return view('contracts.signed-success');
})->name('contracts.signed-success');
Route::get('/contracts/declined', function () {
    return view('contracts.declined');
})->name('contracts.declined');
Route::get('/{any}', function () {
    \$spaPath = public_path('spa/index.html');
    if (File::exists(\$spaPath)) {
        return response()->file(\$spaPath);
    }
    abort(404, 'Frontend not found');
})->where('any', '.*');
WEBPHP

RUN npm ci && npm run build && rm -rf node_modules
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy frontend build output into backend public/spa folder
COPY --from=frontend-builder /frontend/dist /app/public/spa

# Copy frontend assets to public root so php artisan serve can find them
RUN cp -r /app/public/spa/assets /app/public/assets && \
    cp /app/public/spa/manifest.json /app/public/manifest.json 2>/dev/null || true && \
    cp /app/public/spa/favicon.svg /app/public/favicon.svg 2>/dev/null || true && \
    cp /app/public/spa/registerSW.js /app/public/registerSW.js 2>/dev/null || true && \
    cp /app/public/spa/sw.js /app/public/sw.js 2>/dev/null || true && \
    cp /app/public/spa/logo*.png /app/public/ 2>/dev/null || true && \
    cp /app/public/spa/logo*.svg /app/public/ 2>/dev/null || true

RUN chmod -R 775 /app/storage /app/bootstrap/cache 2>/dev/null || true
EXPOSE 8000
CMD ["/bin/bash", "-c", "php artisan config:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan migrate --force && exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
