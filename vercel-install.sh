#!/usr/bin/env bash
set -e
php -v
composer install --no-interaction --prefer-dist
npm install --no-audit --no-fund
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
