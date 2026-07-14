#!/usr/bin/env bash
# MSAS Production Deployment Script
# Run from /var/www/msas/msas-system on your Ubuntu server

set -e

echo "======================================"
echo "  MSAS Production Deploy"
echo "======================================"

# Pull latest code
cd /var/www/msas
git pull origin main

cd /var/www/msas/msas-system

# PHP dependencies (no dev)
echo "[1/8] Installing PHP dependencies..."
composer install --optimize-autoloader --no-dev --no-interaction

# Build frontend assets
echo "[2/8] Building frontend assets..."
npm ci --prefer-offline
npm run build

# Run migrations
echo "[3/8] Running database migrations..."
php artisan migrate --force

# Clear old caches before re-caching
echo "[4/8] Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Re-cache everything
echo "[5/8] Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

# Storage symlink
echo "[6/8] Linking storage..."
php artisan storage:link || true

# Restart queue workers
echo "[7/8] Restarting queue workers..."
php artisan queue:restart
sudo supervisorctl restart msas-worker:*

# Set correct permissions
echo "[8/8] Setting permissions..."
sudo chown -R www-data:www-data /var/www/msas/msas-system/storage
sudo chown -R www-data:www-data /var/www/msas/msas-system/bootstrap/cache
sudo chmod -R 775 /var/www/msas/msas-system/storage
sudo chmod -R 775 /var/www/msas/msas-system/bootstrap/cache

echo ""
echo "======================================"
echo "  Deploy complete!"
echo "======================================"
