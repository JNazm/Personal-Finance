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

echo "==> Starting php-fpm in background..."
php-fpm &

echo "==> Waiting for php-fpm on port 9000..."
for i in $(seq 1 15); do
    if nc -z 127.0.0.1 9000 2>/dev/null; then
        echo "php-fpm is ready."
        break
    fi
    echo "Waiting... ($i)"
    sleep 1
done

echo "==> Starting nginx..."
exec nginx -g "daemon off;"
