#!/bin/sh

echo "==> Clearing stale cache..."
php artisan optimize:clear || true

echo "==> Running database migrations..."
php artisan migrate --force || echo "WARNING: Migrations failed, continuing..."

echo "==> Caching configuration..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true
php artisan storage:link || true

echo "==> Starting php-fpm..."
/usr/local/sbin/php-fpm -D

echo "==> Waiting for php-fpm to be ready..."
sleep 2

echo "==> Starting nginx..."
exec nginx -g "daemon off;"
