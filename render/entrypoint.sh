#!/bin/sh
set -e

cd /app

# Cache framework config/routes/views for production performance. These read
# the real environment variables provided by Render at runtime.
echo "==> Caching configuration, routes and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Running database migrations..."
php artisan migrate --force

# Optionally seed the database (awards + admin users). Set SEED_DATABASE=true
# in the Render dashboard for the first deploy, then set it back to false.
if [ "${SEED_DATABASE}" = "true" ]; then
    echo "==> Seeding database..."
    php artisan db:seed --force
fi

# FrankenPHP listens on the address in SERVER_NAME. Using ":$PORT" (no host)
# serves plain HTTP on the port Render expects (Render terminates TLS).
export SERVER_NAME=":${PORT:-8080}"

echo "==> Starting FrankenPHP on ${SERVER_NAME}..."
exec frankenphp run --config /etc/frankenphp/Caddyfile
