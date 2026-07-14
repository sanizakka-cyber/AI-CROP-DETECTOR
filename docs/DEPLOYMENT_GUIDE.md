# MSAS Production Deployment Guide

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.2+ |
| MySQL | 8.0+ |
| Node.js | 18+ |
| Redis | 7+ |
| Nginx | 1.24+ |
| SSL | Let's Encrypt (Certbot) |

---

## 1. Server Setup (Ubuntu 22.04 LTS)

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server redis-server git unzip curl

# PHP 8.2
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring \
  php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-redis

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js 20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

---

## 2. Database Setup

```sql
-- Run as MySQL root
CREATE DATABASE msas_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'msas_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON msas_production.* TO 'msas_user'@'localhost';
FLUSH PRIVILEGES;
```

---

## 3. Deploy Laravel App

```bash
# Clone or upload code to /var/www/msas
cd /var/www
sudo git clone <your-repo> msas
# OR: upload via FTP/SCP and extract

cd /var/www/msas/msas-system

# Set permissions
sudo chown -R www-data:www-data /var/www/msas
sudo chmod -R 755 /var/www/msas
sudo chmod -R 775 /var/www/msas/msas-system/storage
sudo chmod -R 775 /var/www/msas/msas-system/bootstrap/cache

# Install dependencies
composer install --optimize-autoloader --no-dev

# Environment
cp .env.production .env
# Edit .env and fill in all REPLACE_WITH_* values
nano .env

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Seed database (SKIP in production if already has data)
# php artisan db:seed --force

# Build assets
npm ci && npm run build

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

# Storage symlink
php artisan storage:link
```

---

## 4. Nginx Configuration

Save as `/etc/nginx/sites-available/msas`:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/msas/msas-system/public;
    index index.php;

    # SSL (managed by Certbot)
    ssl_certificate     /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
    gzip_min_length 256;

    # Max upload size
    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Block hidden files
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/msas /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# SSL with Let's Encrypt
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

---

## 5. Queue Worker (Supervisor)

```bash
sudo apt install supervisor -y
sudo nano /etc/supervisor/conf.d/msas-worker.conf
```

```ini
[program:msas-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/msas/msas-system/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/msas/worker.log
stopwaitsecs=3600
```

```bash
sudo mkdir -p /var/log/msas
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start msas-worker:*
```

---

## 6. Scheduled Tasks (Cron)

```bash
sudo crontab -e -u www-data
```

Add:
```
* * * * * cd /var/www/msas/msas-system && php artisan schedule:run >> /dev/null 2>&1
```

---

## 7. Firewall

```bash
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw --force enable
sudo ufw status
```

---

## 8. AI Engine (FastAPI)

```bash
cd /var/www/msas/ai-engine
pip3 install -r requirements.txt

# Create systemd service
sudo nano /etc/systemd/system/msas-ai.service
```

```ini
[Unit]
Description=MSAS AI Engine
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/msas/ai-engine
ExecStart=/usr/bin/python3 -m uvicorn main:app --host 127.0.0.1 --port 8001
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable msas-ai
sudo systemctl start msas-ai
```

---

## 9. Backup Automation

```bash
sudo nano /etc/cron.d/msas-backup
```

```
# Daily database backup at 2am
0 2 * * * www-data mysqldump -u msas_user -pPASSWORD msas_production | gzip > /var/backups/msas/db-$(date +\%Y\%m\%d).sql.gz

# Keep only 30 days of backups
30 2 * * * find /var/backups/msas/ -name "db-*.sql.gz" -mtime +30 -delete
```

---

## 10. Post-Deployment Checklist

- [ ] `APP_DEBUG=false` in .env
- [ ] `APP_ENV=production` in .env
- [ ] All `REPLACE_WITH_*` values filled in .env
- [ ] SSL certificate installed and auto-renewing
- [ ] `php artisan optimize` run
- [ ] Database migrated with `--force`
- [ ] Queue workers running (supervisor)
- [ ] Cron job set up
- [ ] Firewall enabled
- [ ] Test all role logins
- [ ] Test subscription flow end-to-end
- [ ] Test mobile app connectivity (update `EXPO_PUBLIC_API_URL` to production URL)
- [ ] `User::where('is_test_account', true)->forceDelete()` — remove QA accounts
- [ ] Enable monitoring (UptimeRobot, Sentry, etc.)

---

## Mobile App — Update for Production

In `mobile/.env`, change:
```
EXPO_PUBLIC_API_URL=https://yourdomain.com/api
```

Then rebuild APK:
```bash
cd mobile/android
./gradlew assembleRelease
```

The release APK will be at:
`mobile/android/app/build/outputs/apk/release/app-release.apk`

---

## Google Play Store Submission

1. Create a keystore:
```bash
keytool -genkey -v -keystore msas-release.keystore \
  -alias msas -keyalg RSA -keysize 2048 -validity 10000
```

2. Add to `mobile/android/gradle.properties`:
```
MYAPP_UPLOAD_STORE_FILE=msas-release.keystore
MYAPP_UPLOAD_KEY_ALIAS=msas
MYAPP_UPLOAD_STORE_PASSWORD=YOUR_STORE_PASSWORD
MYAPP_UPLOAD_KEY_PASSWORD=YOUR_KEY_PASSWORD
```

3. Update `mobile/android/app/build.gradle` signing config (release block).

4. Build: `./gradlew bundleRelease` → produces `.aab` for Play Store.

5. Create app listing at [play.google.com/console](https://play.google.com/console):
   - App name: **MSAS Livestock & Agro Services**
   - Package: `com.msas.livestock`
   - Category: Business / Agriculture
   - Upload `.aab` file
   - Fill screenshots, description, privacy policy URL

---

## Paystack Integration (Quick Setup)

In `msas-system/app/Http/Controllers/SubscriptionController.php`, replace the "Simulate payment" comment with:

```php
// Initialize Paystack payment
$response = Http::withToken(config('services.paystack.secret'))
    ->post('https://api.paystack.co/transaction/initialize', [
        'email'     => $user->email,
        'amount'    => $amount * 100, // Paystack uses kobo
        'reference' => 'MSAS-' . Str::upper(Str::random(12)),
        'metadata'  => ['plan' => $plan, 'billing_cycle' => $cycle, 'user_id' => $user->id],
        'callback_url' => route('subscription.paystack.callback'),
    ]);

if ($response->successful()) {
    return redirect($response->json('data.authorization_url'));
}
return back()->with('error', 'Payment initialization failed. Please try again.');
```

Add to `config/services.php`:
```php
'paystack' => [
    'public' => env('PAYSTACK_PUBLIC_KEY'),
    'secret' => env('PAYSTACK_SECRET_KEY'),
],
```

Add webhook + callback routes to handle payment confirmation.
