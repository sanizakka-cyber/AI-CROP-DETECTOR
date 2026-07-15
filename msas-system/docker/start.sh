#!/bin/sh
set -e

echo "==> Starting MSAS FarmAI..."

# Ensure storage dirs exist and are writable (including tmp for Blade compiler)
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/tmp
mkdir -p /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Point PHP's temp dir at our writable storage path (avoids /tmp permission issues)
echo "sys_temp_dir = /var/www/html/storage/tmp" > /usr/local/etc/php/conf.d/tempdir.ini
echo "upload_tmp_dir = /var/www/html/storage/tmp" >> /usr/local/etc/php/conf.d/tempdir.ini

# Run migrations FIRST so all tables exist before caching views/config
echo "==> Running migrations..."
php artisan migrate --force

# Cache configuration AFTER migrations so config picks up any env-based logic
echo "==> Caching config & routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Seed if the products table is empty (first deploy)
php artisan db:seed --class=ProductSeeder --force 2>/dev/null || true

echo "==> Starting PHP-FPM..."
php-fpm -D

# Give PHP-FPM a moment to bind
sleep 1

echo "==> Starting Nginx on :8080"
exec nginx -g "daemon off;"
