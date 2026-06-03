# syntax=docker/dockerfile:1

FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
COPY public ./public

ARG VITE_APP_NAME="FN12 App"
ARG VITE_WEBPUSH_VAPID_PUBLIC_KEY=
ENV VITE_APP_NAME=${VITE_APP_NAME}
ENV VITE_WEBPUSH_VAPID_PUBLIC_KEY=${VITE_WEBPUSH_VAPID_PUBLIC_KEY}

RUN npm run build

FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN composer dump-autoload --optimize --classmap-authoritative

FROM php:8.3-fpm-bookworm AS php

RUN apt-get update && apt-get install -y --no-install-recommends \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        opcache \
        pdo_mysql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-scouting-app.ini

WORKDIR /var/www/html

COPY --from=vendor --chown=www-data:www-data /app ./

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

FROM nginx:1.27-alpine AS web

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=php /var/www/html/public /var/www/html/public

EXPOSE 80
