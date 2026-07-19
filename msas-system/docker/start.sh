#!/bin/sh
set -e

echo "==> Starting MSAS FarmAI..."

# Ensure storage dirs exist
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/tmp
mkdir -p /var/www/html/bootstrap/cache

# Point PHP's temp dir at our writable storage path
echo "sys_temp_dir = /var/www/html/storage/tmp" > /usr/local/etc/php/conf.d/tempdir.ini
echo "upload_tmp_dir = /var/www/html/storage/tmp" >> /usr/local/etc/php/conf.d/tempdir.ini

# Run migrations FIRST so all tables exist before caching views/config
echo "==> Running migrations..."
php artisan migrate --force

# Cache configuration, routes and views (runs as root; chown follows below)
echo "==> Caching config, routes & views..."
php artisan config:cache
php artisan route:cache  2>/dev/null || echo "Route cache skipped (closure routes present)"
php artisan view:cache   2>/dev/null || echo "View cache skipped"

# Create storage symlink so uploaded files are publicly accessible
php artisan storage:link --force 2>/dev/null || true

# Seed if the products table is empty (first deploy)
php artisan db:seed --class=ProductSeeder --force 2>/dev/null || true

# Fix permissions AFTER artisan commands so compiled/cached files are also
# owned by www-data and writable at runtime (view:cache runs as root and
# creates files owned by root; php-fpm runs as www-data and needs write access
# to storage/framework/views for any runtime Blade compilation).
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "==> Starting PHP-FPM..."
php-fpm -D

# Give PHP-FPM a moment to bind
sleep 1

echo "==> Starting Nginx on :8080"
exec nginx -g "daemon off;"
