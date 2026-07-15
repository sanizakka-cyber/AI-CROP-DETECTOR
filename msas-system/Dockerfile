FROM php:8.2-fpm-alpine

# ── System packages ──────────────────────────────────────────────────────────
RUN apk add --no-cache \
    nginx \
    git unzip curl \
    postgresql-dev \
    libzip-dev libpng-dev oniguruma-dev \
    freetype-dev libjpeg-turbo-dev

# ── PHP extensions ────────────────────────────────────────────────────────────
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    pdo pdo_pgsql pgsql \
    mbstring zip gd exif pcntl bcmath opcache

# ── PHP production tuning ─────────────────────────────────────────────────────
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

# ── Composer ──────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ── Install PHP deps (layer-cached when composer.lock unchanged) ───────────────
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .

# ── Finish Composer (Vite assets pre-built and committed in public/build/) ────
RUN composer dump-autoload --optimize

# ── Permissions ───────────────────────────────────────────────────────────────
RUN chown -R www-data:www-data /var/www/html \
 && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# ── Nginx + start script ──────────────────────────────────────────────────────
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/start.sh   /start.sh
RUN chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]
