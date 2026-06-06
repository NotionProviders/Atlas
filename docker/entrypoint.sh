#!/bin/sh
set -e

cd /var/www/html

mkdir -p \
    bootstrap/cache \
    database \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

chown -R www-data:www-data storage bootstrap/cache database
chmod -R 775 storage bootstrap/cache database

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    mkdir -p "$(dirname "$DB_FILE")"
    if [ ! -f "$DB_FILE" ]; then
        touch "$DB_FILE"
    fi
    chown www-data:www-data "$DB_FILE"
fi

php artisan migrate --force --no-interaction

php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

exec "$@"
