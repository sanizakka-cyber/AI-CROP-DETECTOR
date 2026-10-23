#!/bin/sh
set -e

echo "==> Starting MSAS FarmAI..."

# Ensure storage dirs exist
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/tmp/nginx_body
mkdir -p /var/www/html/bootstrap/cache

# Point PHP's temp dir at our writable storage path
echo "sys_temp_dir = /var/www/html/storage/tmp" > /usr/local/etc/php/conf.d/tempdir.ini
echo "upload_tmp_dir = /var/www/html/storage/tmp" >> /usr/local/etc/php/conf.d/tempdir.ini

# Route PHP-FPM worker output through container stderr so Render captures it
echo "catch_workers_output = yes" >> /usr/local/etc/php-fpm.d/www.conf
echo "decorate_workers_output = no" >> /usr/local/etc/php-fpm.d/www.conf

# Staging: APP_KEY comes from the Render-generated APP_KEY_SEED (render.staging.yaml)
# when no APP_KEY is set, so no one ever has to create or paste one. Not
# printed. Production keeps its own APP_KEY and never takes this branch.
if [ -z "$APP_KEY" ] && [ -n "$APP_KEY_SEED" ] && [ "$APP_ENV" != "production" ]; then
  APP_KEY="$(php -r 'echo "base64:".base64_encode(hash("sha256", getenv("APP_KEY_SEED"), true));')"
  export APP_KEY
fi

# Discover packages now that env vars are available (skipped during Docker build)
echo "==> Discovering packages..."
php artisan package:discover --ansi 2>/dev/null || true

# Run migrations FIRST so all tables exist before caching views/config
echo "==> Running migrations..."
php artisan migrate --force

# Clear all stale caches BEFORE rebuilding — ensures env var changes are picked up
echo "==> Clearing stale caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear 2>/dev/null || true

# Cache configuration, routes and views (runs as root; chown follows below)
echo "==> Caching config, routes & views..."
php artisan config:cache
php artisan route:cache  2>/dev/null || echo "Route cache skipped (closure routes present — routes resolved dynamically)"
php artisan view:cache   2>/dev/null || echo "View cache skipped"

# Create storage symlink so uploaded files are publicly accessible
php artisan storage:link --force 2>/dev/null || true

# Seed if the products table is empty (first deploy)
php artisan db:seed --class=ProductSeeder --force 2>/dev/null || true

# Upsert super admin account on every deploy (idempotent)
php artisan db:seed --class=SuperAdminSeeder --force 2>/dev/null || true

# Staging only: the four QA accounts, passwords from Render-generated secrets
# (never printed). The seeder itself refuses to run unless APP_ENV=staging.
if [ "$APP_ENV" = "staging" ]; then
  php artisan db:seed --class=StagingQaAccountsSeeder --force || true
fi

# Fix permissions AFTER artisan commands so compiled/cached files are also
# owned by www-data and writable at runtime (view:cache runs as root and
# creates files owned by root; php-fpm runs as www-data and needs write access
# to storage/framework/views for any runtime Blade compilation).
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Laravel's scheduler (routes/console.php: subscriptions:expire/remind,
# db:backup, system:health, digest:weekly) requires something to invoke
# `artisan schedule:run` every minute -- this container previously only
# ran php-fpm + nginx, so none of those commands, including the daily
# database backup, had ever actually run in production. Runs as www-data
# (not root) via /etc/crontabs/www-data so the job has exactly the same
# filesystem permissions php-fpm's own workers already run with.
echo "==> Installing Laravel scheduler cron job..."
echo "* * * * * cd /var/www/html && php artisan schedule:run >> /proc/1/fd/1 2>> /proc/1/fd/2" > /etc/crontabs/www-data
chown www-data:www-data /etc/crontabs/www-data
chmod 600 /etc/crontabs/www-data

echo "==> Starting cron..."
crond -b -l 2

echo "==> Starting PHP-FPM..."
php-fpm --nodaemonize &

# Give PHP-FPM a moment to bind
sleep 1

echo "==> Starting Nginx on :8080"
exec nginx -g "daemon off;"
