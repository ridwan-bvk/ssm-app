# Compiles the Vite/Vue frontend (public/build/* + the PWA service worker at
# public/sw.js) — both are gitignored build output, so without this stage a
# fresh clone would produce an image with no CSS/JS at all ("Vite manifest
# not found").
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

FROM dunglas/frankenphp:1-php8.3

# Install system dependencies for the same PHP extensions the CI4 app needed
# (intl, gd) plus zip for spatie/laravel-backup and default-mysql-client for
# the entrypoint's DB wait-loop.
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    default-mysql-client \
    libicu-dev \
    zip \
    unzip \
    tzdata \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

ENV TZ=Asia/Jakarta
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV SERVER_NAME=:80

WORKDIR /app

COPY --chown=www-data:www-data . /app
COPY --from=frontend --chown=www-data:www-data /app/public/build /app/public/build
COPY --from=frontend --chown=www-data:www-data /app/public/sw.js /app/public/sw.js

RUN composer install --no-interaction --optimize-autoloader --no-dev \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

COPY docker/Caddyfile /etc/caddy/Caddyfile

EXPOSE 80 443

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
