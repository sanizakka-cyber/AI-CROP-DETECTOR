#!/bin/bash
# Run this in cPanel Terminal once files are uploaded.
# Usage: bash cpanel-setup.sh
# ─────────────────────────────────────────────────────────────────────────────

set -e

echo "=== MSAS FarmAI — cPanel Post-Upload Setup ==="

# 1. Install Composer dependencies (no dev packages for production)
echo "[1/7] Installing Composer packages..."
composer install --no-dev --optimize-autoloader

# 2. Copy .env.production to .env
echo "[2/7] Activating production .env..."
cp .env.production .env

# 3. Generate app key (safe to run even if key already set in .env.production)
echo "[3/7] Ensuring APP_KEY is set..."
php artisan key:generate --force

# 4. Run database migrations
echo "[4/7] Running migrations..."
php artisan migrate --force

# 5. Seed the database (26 products + roles + default admin)
echo "[5/7] Seeding database..."
php artisan db:seed --force

# 6. Clear and cache all config/routes/views for production performance
echo "[6/7] Caching config, routes, and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Set storage permissions
echo "[7/7] Setting storage and cache permissions..."
chmod -R 755 storage bootstrap/cache
php artisan storage:link

echo ""
echo "=== Setup complete! ==="
echo ""
echo "Checklist before going live:"
echo "  [ ] Fill in DB_PASSWORD, PAYSTACK keys, MAIL_PASSWORD in .env"
echo "  [ ] Confirm APP_URL=https://msasagro.com in .env"
echo "  [ ] Delete QA test accounts: php artisan tinker --execute=\"App\Models\User::where('is_test_account',true)->forceDelete();\""
echo "  [ ] Visit https://msasagro.com to verify"
