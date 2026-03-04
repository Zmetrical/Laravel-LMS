#!/bin/bash
set -e

# Run Laravel setup on container start
php artisan config:cache
php artisan route:cache
php artisan migrate --force

exec "$@"