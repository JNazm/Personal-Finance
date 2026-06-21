#!/bin/sh

echo "==> Clearing stale cache..."
php artisan optimize:clear || true

echo "==> Running database migrations..."
php artisan migrate --force || echo "WARNING: Migrations failed, continuing..."

echo "==> Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link || true

echo "==> Starting services..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
