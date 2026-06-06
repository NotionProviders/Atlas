# syntax=docker/dockerfile:1

FROM composer:2.8 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative --no-interaction

FROM php:8.3-fpm-alpine AS runtime

# Pre-built extension binaries — same extensions, much faster than compiling from source.
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
    bcmath \
    intl \
    mbstring \
    opcache \
    pdo_pgsql \
    pdo_sqlite \
    zip

RUN apk add --no-cache \
    nginx \
    supervisor \
    wget \
    && rm -rf /var/cache/apk/* /tmp/*

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html

RUN mkdir -p \
    bootstrap/cache \
    database \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache database

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/99-laravel.ini
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

EXPOSE 3000

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD wget -qO- http://127.0.0.1:3000/up || exit 1

ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
