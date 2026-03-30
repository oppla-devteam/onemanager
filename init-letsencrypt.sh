#!/bin/bash
# SSL Setup Script for Docker Deployment
# Usage: ./init-letsencrypt.sh

set -e

domains=(onemanager.yourdomain.com api.onemanager.yourdomain.com)
email="admin@yourdomain.com"
staging=0 # Set to 1 for testing

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if docker-compose is installed
if ! command -v docker-compose &> /dev/null; then
    log_error "docker-compose is not installed"
    exit 1
fi

# Create directories
log_info "Creating directories..."
mkdir -p certbot/conf
mkdir -p certbot/www

# Download recommended TLS parameters
log_info "Downloading TLS parameters..."
curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot-nginx/certbot_nginx/_internal/tls_configs/options-ssl-nginx.conf > certbot/conf/options-ssl-nginx.conf
curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot/certbot/ssl-dhparams.pem > certbot/conf/ssl-dhparams.pem

# Create dummy certificate
log_info "Creating dummy certificate for ${domains[0]}..."
path="/etc/letsencrypt/live/${domains[0]}"
mkdir -p "certbot/conf/live/${domains[0]}"
docker-compose -f docker-compose.prod.yml run --rm --entrypoint "\
  openssl req -x509 -nodes -newkey rsa:4096 -days 1\
    -keyout '$path/privkey.pem' \
    -out '$path/fullchain.pem' \
    -subj '/CN=localhost'" certbot

# Start nginx
log_info "Starting nginx..."
docker-compose -f docker-compose.prod.yml up --force-recreate -d nginx

# Delete dummy certificate
log_info "Deleting dummy certificate..."
docker-compose -f docker-compose.prod.yml run --rm --entrypoint "\
  rm -Rf /etc/letsencrypt/live/${domains[0]} && \
  rm -Rf /etc/letsencrypt/archive/${domains[0]} && \
  rm -Rf /etc/letsencrypt/renewal/${domains[0]}.conf" certbot

# Request Let's Encrypt certificate
log_info "Requesting Let's Encrypt certificate..."

# Select staging or production
if [ $staging != "0" ]; then
  staging_arg="--staging"
  log_warn "Using staging environment"
else
  staging_arg=""
  log_info "Using production environment"
fi

# Build domain args
domain_args=""
for domain in "${domains[@]}"; do
  domain_args="$domain_args -d $domain"
done

# Request certificate
docker-compose -f docker-compose.prod.yml run --rm --entrypoint "\
  certbot certonly --webroot -w /var/www/certbot \
    $staging_arg \
    $domain_args \
    --email $email \
    --rsa-key-size 4096 \
    --agree-tos \
    --force-renewal" certbot

# Reload nginx
log_info "Reloading nginx..."
docker-compose -f docker-compose.prod.yml exec nginx nginx -s reload

log_info "SSL certificates installed successfully!"
log_info "Certificates location: ./certbot/conf/live/${domains[0]}/"
