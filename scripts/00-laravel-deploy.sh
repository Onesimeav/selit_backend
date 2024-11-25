#!/usr/bin/env bash
echo "Running composer"
composer install --no-dev --working-dir=/var/www/html

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Running migrations..."
php artisan migrate --force

echo "Starting the queue listener..."
php artisan queue:listen &

echo "Start the queue worker..."
php artisan queue:work --daemon &

echo "Publishing laravel request docs assets"
php artisan vendor:publish --tag=request-docs-assets
