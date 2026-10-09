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

# ── Redis extension ───────────────────────────────────────────────────────────
RUN apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apk del --no-cache .phpize-deps

# ── PHP production tuning ─────────────────────────────────────────────────────
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Phase 7 fix: the official php:8.2-fpm-alpine image ships NO php.ini at all
# unless one is copied in, so PHP fell back to its compiled-in defaults --
# upload_max_filesize=2M, post_max_size=8M. Every diagnostic-scan validation
# rule in the app (DiagnosticController, DiagnoseApiController) allows up to
# 10MB, and nginx's client_max_body_size (docker/nginx.conf) allows 20M --
# but any upload between 2MB and 10MB was silently rejected by PHP itself,
# before Laravel's request object was even fully populated, producing the
# generic "The image failed to upload" message. Confirmed live during the
# Phase 6 audit's authenticated scan test (a real 2.95MB photo failed; an
# 800px/284KB version of the same photo succeeded). Set to comfortably
# exceed the app's own 10MB (10240 KB) validation ceiling, leaving headroom
# for multipart boundary overhead and the request's other form fields.
RUN echo "upload_max_filesize=12M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "post_max_size=15M" >> /usr/local/etc/php/conf.d/uploads.ini

# ── Composer ──────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ── Install PHP deps (layer-cached when composer.lock unchanged) ───────────────
# Security audit found composer.lock had drifted a month out of sync with
# composer.json (two required packages weren't in it at all) — invisible
# in practice only because this ran `update` instead of `install`, which
# silently re-resolves fresh every build instead of failing loudly on a
# stale lock. That also meant no two deploys were guaranteed to run the
# same dependency versions. `install` is the reproducible, intended
# command; composer.lock is now regenerated to match composer.json.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .

# ── Finish Composer (Vite assets pre-built and committed in public/build/) ────
# --no-scripts skips @php artisan package:discover which requires a .env file
# that doesn't exist at build time. package:discover runs at container startup instead.
RUN composer dump-autoload --optimize --no-scripts

# ── Permissions ───────────────────────────────────────────────────────────────
RUN chown -R www-data:www-data /var/www/html \
 && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# ── Nginx + start script ──────────────────────────────────────────────────────
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/start.sh   /start.sh
RUN chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]
