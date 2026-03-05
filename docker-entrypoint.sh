#!/bin/bash
set -e

PORT=${PORT:-80}

sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/*:80/*:$PORT/g" /etc/apache2/sites-available/000-default.conf

php artisan route:clear
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan migrate --force

exec "$@"