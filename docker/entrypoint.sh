#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
    cp .env.docker .env
fi

if ! grep -qE '^APP_KEY=base64:.+' .env; then
    php artisan key:generate --force --no-interaction
fi

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

if [ ! -f /var/www/html/resources/data/atlas.json ]; then
    echo "FATAL: /var/www/html/resources/data/atlas.json is missing from the image." >&2
    exit 1
fi

php artisan migrate --force --no-interaction

php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:clear --no-interaction
php artisan view:cache --no-interaction

chown -R www-data:www-data storage bootstrap/cache database
chmod -R 775 storage bootstrap/cache database

exec "$@"
