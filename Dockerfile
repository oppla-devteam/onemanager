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

RUN npm ci && npm run build && rm -rf node_modules

RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY --from=frontend-builder /frontend/dist /app/public/spa

RUN chmod -R 775 /app/storage /app/bootstrap/cache 2>/dev/null || true

EXPOSE 8000

CMD ["/bin/bash", "-c", "php artisan config:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan migrate --force && exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
