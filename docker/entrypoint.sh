#!/bin/sh

echo "====== ENTRYPOINT START ======"

echo "==> Clearing ALL cached files..."
rm -f /var/www/html/bootstrap/cache/*.php

echo "==> Running database migrations..."
php artisan migrate --force && echo "Migrations OK" || echo "WARNING: Migrations failed"

echo "==> Creating storage link..."
php artisan storage:link || true

echo "==> Starting php-fpm in background..."
php-fpm &
FPM_PID=$!
echo "php-fpm PID: $FPM_PID"

echo "==> Waiting up to 15s for php-fpm on port 9000..."
READY=0
for i in $(seq 1 15); do
    if nc -z 127.0.0.1 9000 2>/dev/null; then
        echo "php-fpm is ready (${i}s)"
        READY=1
        break
    fi
    if ! kill -0 $FPM_PID 2>/dev/null; then
        echo "ERROR: php-fpm process died!"
        break
    fi
    echo "  still waiting... ${i}/15"
    sleep 1
done

if [ "$READY" -eq 0 ]; then
    echo "ERROR: php-fpm never became ready."
fi

echo "==> Starting nginx..."
exec nginx -g "daemon off;"
