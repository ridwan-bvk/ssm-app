#!/bin/bash
set -e

if [ ! -d "vendor" ] || [ -z "$(ls -A vendor)" ]; then
    echo "Vendor directory not found or empty. Installing dependencies..."
    composer install --no-interaction --optimize-autoloader --no-dev
fi

echo "Waiting for database connection..."
DB_HOST="${DB_HOST:-db}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"
DB_PORT="${DB_PORT:-3306}"

for i in $(seq 1 30); do
    if mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" -P"$DB_PORT" --ssl-mode=DISABLED -e "SELECT 1" 2>/dev/null; then
        echo "Database is ready!"
        break
    fi
    echo "Waiting for database... attempt $i/30"
    sleep 2
done

if [ ! -f ".env" ]; then
    cp .env.example .env
fi

php artisan key:generate --force
php artisan migrate --force
php artisan storage:link || true

echo "Caching configuration for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize

chown -R www-data:www-data /app/storage /app/bootstrap/cache

exec "$@"
