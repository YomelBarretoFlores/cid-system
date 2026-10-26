# syntax=docker/dockerfile:1

# Demo and staging image (docs/despliegue.md): web, queue worker and scheduler in one container.
FROM php:8.5-fpm-trixie AS base

RUN apt-get update \
    && apt-get install -y --no-install-recommends libfreetype-dev libicu-dev libjpeg62-turbo-dev libpng-dev libpq-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath gd intl pcntl pdo_pgsql pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

FROM base AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

# The Filament theme imports its CSS from vendor/, so the build needs the PHP dependencies.
FROM node:22-trixie-slim AS assets

WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci
COPY .gitignore vite.config.js ./
COPY resources resources
COPY app app
COPY --from=vendor /app/vendor vendor
RUN npm run build

FROM base AS app

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl nginx supervisor \
    && rm -rf /var/lib/apt/lists/* /etc/nginx/sites-enabled/default \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

COPY docker/app/php.ini /usr/local/etc/php/conf.d/zz-ciln.ini
COPY docker/app/nginx.conf /etc/nginx/conf.d/ciln.conf
COPY docker/app/supervisord.conf /etc/supervisor/conf.d/ciln.conf
COPY docker/app/entrypoint.sh /usr/local/bin/ciln-entrypoint

WORKDIR /var/www/html
COPY --from=vendor /app/vendor vendor
COPY . .
COPY --from=assets /app/public/build public/build

RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && chmod +x /usr/local/bin/ciln-entrypoint \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080

# The start period covers the wait for PostgreSQL (up to about 150 s) plus migrations.
HEALTHCHECK --interval=30s --timeout=5s --start-period=240s --retries=3 CMD curl -fsS http://127.0.0.1:8080/up || exit 1

ENTRYPOINT ["ciln-entrypoint"]
