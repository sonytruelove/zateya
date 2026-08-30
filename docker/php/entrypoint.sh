#!/bin/sh
set -e

# Ждём готовности базы и применяем миграции при первом запуске веб-контейнера.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction || true
fi

if [ "${RUN_SEED:-false}" = "true" ]; then
    php artisan db:seed --force --no-interaction || true
fi

php artisan config:cache || true
php artisan route:cache || true
php artisan event:cache || true

exec "$@"
