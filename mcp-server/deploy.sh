#!/bin/bash
# Deploy MCP Server to production
# Run this on the production server (pedro.oppla.club)

set -e

echo "=== Deploying OneManager MCP Server ==="

# 1. Pull latest code
cd /var/www/onemanager
echo ">> Pulling latest code..."
git pull origin master

# 2. Install MCP server dependencies and build
echo ">> Building MCP server..."
cd /var/www/onemanager/mcp-server
npm install --production=false
npm run build

# 3. Run Laravel migration (add fbnomande as admin)
echo ">> Running Laravel migrations..."
cd /var/www/onemanager/backend
php artisan migrate --force

# 4. Start/restart MCP server with PM2
echo ">> Starting MCP server with PM2..."
cd /var/www/onemanager/mcp-server
pm2 delete onemanager-mcp 2>/dev/null || true
pm2 start ecosystem.config.cjs
pm2 save

# 5. Test health endpoint
echo ">> Testing health endpoint..."
sleep 2
curl -s http://127.0.0.1:3100/health | head -c 200
echo ""

echo ""
echo "=== Done! ==="
echo ""
echo "IMPORTANT: You still need to:"
echo "1. Add nginx config from mcp-server/nginx-mcp.conf to /etc/nginx/sites-available/pedro.oppla.club"
echo "2. Reload nginx: sudo nginx -t && sudo systemctl reload nginx"
echo "3. Add https://pedro.oppla.club/mcp/auth/callback as a redirect URI in the Bink OAuth app settings"
echo ""
