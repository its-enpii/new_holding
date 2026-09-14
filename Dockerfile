# syntax=docker/dockerfile:1.7
#
# Runtime image for the Holding portal (PHP-FPM 8.4).
#
# Like the akubumdes / new_sidbm stacks, app source, vendor/, and .env are
# bind-mounted at runtime by the `app` service, so the image only ships the
# runtime: system packages, PHP extensions, config, and the entrypoint. Code
# edits on the host apply after `docker compose restart app` without a rebuild.
#
# The one thing baked into the image is `public/hot`-free frontend output: the
# node stage below builds Vite assets so the container serves a real build even
# when the host has not run `npm run build`.

ARG UID=1000
ARG GID=1000

# ---------------------------------------------------------------------------
# Stage 1: composer deps, only needed so the node stage can resolve the Ziggy
# JS entry (resources/js/app.js imports ../../vendor/tightenco/ziggy).
# ---------------------------------------------------------------------------
FROM composer:2 AS ziggy

WORKDIR /app

COPY composer.json composer.lock ./
COPY bootstrap/providers.php ./bootstrap/providers.php
RUN composer install --no-interaction --no-dev --no-scripts --no-autoloader --prefer-dist

# ---------------------------------------------------------------------------
# Stage 2: frontend assets (resources/js + vite) -> /app/public/build
# ---------------------------------------------------------------------------
FROM node:22-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json vite.config.js ./
RUN npm ci --no-audit --no-fund

COPY --from=ziggy /app/vendor/tightenco/ziggy ./vendor/tightenco/ziggy
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---------------------------------------------------------------------------
# Stage 3: PHP-FPM runtime
# ---------------------------------------------------------------------------
FROM php:8.4-fpm-bookworm

ARG UID=1000
ARG GID=1000

# Extensions: no ext-redis on purpose — the app talks to Redis through the
# pure-PHP predis client (composer.json), so the bus works on any host without
# compiling a PECL extension.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libicu-dev \
        libonig-dev \
        libsqlite3-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        opcache \
        pdo_mysql \
        pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Composer (pinned to v2 to match the lock file format).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Match the host UID/GID so bind-mounted files are not owned by root.
RUN groupmod -o -g "$GID" www-data \
    && usermod -o -u "$UID" -g www-data www-data

WORKDIR /var/www/html

COPY docker/app/php.ini       /usr/local/etc/php/conf.d/99-holding.ini
COPY docker/app/entrypoint.sh /usr/local/bin/holding-entrypoint
RUN chmod +x /usr/local/bin/holding-entrypoint

# The bind-mounted host tree shadows /var/www/html/public/build, so the image
# keeps a second copy that nginx falls back to when the host has not built its
# assets (`@built_assets` in docker/nginx/default.conf).
COPY --from=frontend --chown=www-data:www-data /app/public/build /opt/holding/build

USER www-data

ENTRYPOINT ["holding-entrypoint"]
CMD ["php-fpm"]
