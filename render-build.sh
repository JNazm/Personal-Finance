#!/bin/sh
set -e

echo "==> Running database migrations..."
php artisan migrate --force

echo "==> Clearing and caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Build complete."
