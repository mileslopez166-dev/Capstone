#!/bin/sh
set -eu

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

if [ ! -e public/storage ]; then
    php artisan storage:link --no-interaction >/dev/null 2>&1 || true
fi

php artisan config:clear --no-interaction >/dev/null 2>&1 || true
php artisan view:clear --no-interaction >/dev/null 2>&1 || true

if [ "${APP_ENV:-production}" = "production" ]; then
    php artisan config:cache --no-interaction
    php artisan view:cache --no-interaction
fi

exec "$@"
