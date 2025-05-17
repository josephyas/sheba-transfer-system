#!/bin/bash

php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

composer install --optimize-autoloader --no-dev

php artisan config:cache
php artisan route:cache
php artisan optimize

echo "Laravel optimized for production environment."
