#!/bin/bash
set -e

# Run Laravel setup on container start
php artisan route:clear        # ← add this
php artisan config:clear       # ← add this
php artisan config:cache
php artisan route:cache
php artisan migrate --force

exec "$@"