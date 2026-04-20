# ===== Stage 1: Composer dependencies =====
FROM composer:latest AS vendor

WORKDIR /app
COPY app/composer.json app/composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY app/ .
RUN composer dump-autoload --optimize --no-dev

# ===== Stage 2: Frontend assets (jika ada) =====
FROM node:20-alpine AS assets

WORKDIR /app
COPY app/package.json app/package-lock.json* ./
RUN npm ci --silent 2>/dev/null || true

COPY app/ .
RUN npm run build 2>/dev/null || mkdir -p public/build

# ===== Stage 3: Production image =====
FROM php:8.3-fpm-alpine

# Install PHP extensions yang dibutuhkan Laravel
RUN apk add --no-cache \
    nginx \
    supervisor \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        zip \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis

# Konfigurasi PHP untuk production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Konfigurasi Nginx
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Konfigurasi Supervisor (Nginx + PHP-FPM)
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf

WORKDIR /var/www/html

# Copy aplikasi dari stage sebelumnya
COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build

# Permission Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
