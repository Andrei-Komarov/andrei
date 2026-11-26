#!/usr/bin/env bash

set -euo pipefail

cd "${APP_DIR:-/var/www/ebay}"

php artisan down --retry=60

cleanup() {
    php artisan up
}

trap cleanup EXIT

composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build

php artisan migrate --force --no-interaction
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan schedule:interrupt

sudo install -m 0644 deploy/systemd/ebay-scheduler.service /etc/systemd/system/ebay-scheduler.service
sudo systemctl daemon-reload
sudo systemctl enable ebay-scheduler
sudo systemctl restart php8.3-fpm nginx ebay-scheduler
