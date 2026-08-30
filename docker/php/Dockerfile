# syntax=docker/dockerfile:1

# --- Слой сборки зависимостей ---
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --no-progress \
    --ignore-platform-req=ext-sockets --ignore-platform-req=ext-pcntl

# --- Основной образ приложения (PHP 8.5 + RoadRunner) ---
FROM php:8.5-cli-alpine AS app

RUN apk add --no-cache postgresql-dev linux-headers $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pcntl sockets bcmath opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# RoadRunner — сервер приложений для Laravel Octane
COPY --from=ghcr.io/roadrunner-server/roadrunner:2024 /usr/bin/rr /usr/local/bin/rr

WORKDIR /app
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

USER www-data
EXPOSE 8080
ENTRYPOINT ["entrypoint"]
CMD ["php", "artisan", "octane:start", "--server=roadrunner", "--host=0.0.0.0", "--port=8080"]
