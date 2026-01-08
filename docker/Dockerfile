# syntax=docker/dockerfile:1
# Laravel 12 + Vue 3 + Inertia.js + SSR
# PHP 8.5.1 | Node 25 | Nginx 1.29.4

# Stage 1: Build frontend
FROM node:25-bookworm-slim AS node-builder
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources/ ./resources/
COPY vite.config.ts tsconfig.json eslint.config.js components.json ./
COPY public/ ./public/
RUN npm run build

# Stage 2: Install PHP dependencies
FROM composer:latest AS composer-builder
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev

# Stage 3: Production
FROM php:8.5.1-fpm-bookworm AS production

ARG APP_ENV=production
ARG APP_DEBUG=false

ENV APP_ENV=${APP_ENV} \
    APP_DEBUG=${APP_DEBUG} \
    PHP_OPCACHE_ENABLE=1 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
    PHP_MEMORY_LIMIT=128M \
    PHP_MAX_EXECUTION_TIME=60 \
    PHP_UPLOAD_MAX_FILESIZE=20M \
    PHP_POST_MAX_SIZE=25M \
    DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
    libzip-dev libicu-dev libonig-dev libxml2-dev libpq-dev \
    jpegoptim optipng pngquant gifsicle libavif-bin \
    git openssh-client supervisor gnupg2 ca-certificates lsb-release \
    nodejs npm default-mysql-client postgresql-client redis-tools \
    curl unzip cron procps \
    && rm -rf /var/lib/apt/lists/*

# Install Nginx from official repository
RUN curl -fsSL https://nginx.org/keys/nginx_signing.key | gpg --dearmor -o /usr/share/keyrings/nginx-archive-keyring.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/nginx-archive-keyring.gpg] http://nginx.org/packages/mainline/debian bookworm nginx" > /etc/apt/sources.list.d/nginx.list \
    && apt-get update && apt-get install -y --no-install-recommends nginx \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) pdo_mysql pdo_pgsql gd zip intl opcache pcntl bcmath exif

RUN pecl install redis && docker-php-ext-enable redis

RUN groupadd -g 1000 www && useradd -u 1000 -g www -m -s /bin/bash www \
    && usermod -aG www-data www

WORKDIR /var/www/html

COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

COPY --from=composer-builder /app /var/www/html
COPY --from=node-builder /app/public/build /var/www/html/public/build
COPY --from=node-builder /app/bootstrap/ssr /var/www/html/bootstrap/ssr

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

RUN mkdir -p storage/app/public storage/framework/{cache/data,sessions,views} \
    storage/logs storage/media-library bootstrap/cache \
    /var/log/php /var/log/supervisor /run/php \
    && chown -R www:www /var/www/html \
    && chmod -R 775 storage bootstrap/cache \
    && ln -sf /var/www/html/storage/app/public /var/www/html/public/storage

# Note: Scheduler is handled by supervisor, not cron (see supervisord.conf)

EXPOSE 80 13714

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
