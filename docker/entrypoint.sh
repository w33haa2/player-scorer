#!/usr/bin/env bash
set -e

cd /var/www/html

echo "==> Waiting for MySQL to be ready..."
until mysqladmin ping -h "${DB_HOST:-mysql}" -P "${DB_PORT:-3306}" --silent; do
    sleep 2
done
echo "==> MySQL is up."

# Install PHP dependencies if they are missing. The vendor/ directory lives in
# a container-managed volume, so it starts empty on a fresh stack.
if [ ! -f "vendor/autoload.php" ]; then
    echo "==> Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

# Install Node dependencies if the (container-managed) node_modules is empty.
# This must be built inside the container to get the correct platform binaries.
if [ -z "$(ls -A node_modules 2>/dev/null)" ]; then
    echo "==> Installing NPM dependencies..."
    npm install
fi

# Ensure an application key exists.
if ! grep -q "^APP_KEY=base64" .env 2>/dev/null; then
    echo "==> Generating application key..."
    php artisan key:generate --force
fi

echo "==> Running migrations & seeders..."
php artisan migrate --seed --force

echo "==> Starting Vite dev server (5173) and Laravel dev server (8000)..."
npm run dev -- --host 0.0.0.0 &
php artisan serve --host=0.0.0.0 --port=8000
