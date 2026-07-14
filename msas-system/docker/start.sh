#!/bin/sh
set -e

echo "==> Starting MSAS FarmAI..."

# Ensure storage dirs exist and are writable
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Cache configuration (fast startup)
echo "==> Caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create sessions table if using database driver
echo "==> Running migrations..."
php artisan migrate --force

# Seed if the products table is empty (first deploy)
php artisan db:seed --class=ProductSeeder --force 2>/dev/null || true

echo "==> Starting PHP-FPM..."
php-fpm -D

# Give PHP-FPM a moment to bind
sleep 1

echo "==> Starting Nginx on :8080"
exec nginx -g "daemon off;"
