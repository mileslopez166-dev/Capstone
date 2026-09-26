# syntax=docker/dockerfile:1

FROM node:22-bookworm AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js postcss.config.js tailwind.config.js ./
RUN npm run build


FROM composer:2 AS composer-bin


FROM php:8.3-cli-bookworm AS vendor

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libonig-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        mbstring \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer-bin /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader

COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction --no-scripts \
    && php artisan package:discover --ansi --no-interaction


FROM php:8.3-apache-bookworm AS runtime

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        exif \
        gd \
        mbstring \
        opcache \
        pdo_mysql \
        zip \
    && a2enmod headers rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/production.ini /usr/local/etc/php/conf.d/production.ini
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint

COPY --from=vendor --chown=www-data:www-data /app /var/www/html
COPY --from=assets --chown=www-data:www-data /app/public/build /var/www/html/public/build

RUN chmod +x /usr/local/bin/docker-entrypoint \
    && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_URL=https://smartlearn6.site \
    LOG_CHANNEL=stderr

EXPOSE 80

ENTRYPOINT ["docker-entrypoint"]
CMD ["apache2-foreground"]
