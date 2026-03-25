#!/bin/sh
set -e

# Wait for MySQL to be ready
echo "Waiting for MySQL..."
until php artisan db:monitor --databases=mysql > /dev/null 2>&1; do
    sleep 2
done
echo "MySQL is ready."

# Generate APP_KEY if missing
if [ -z "$(grep '^APP_KEY=.\+' /var/www/.env)" ]; then
    echo "No APP_KEY found, generating..."
    php artisan key:generate --force
fi

# Storage symlink
php artisan storage:link --force 2>/dev/null || true

# Run migrations (--seed only if the database is fresh)
if [ "$(php artisan migrate:status 2>/dev/null | grep -c 'Ran')" -eq 0 ]; then
    echo "Fresh database detected, running migrate --seed..."
    php artisan migrate --seed --force
else
    echo "Existing database detected, running migrate..."
    php artisan migrate --force
fi

# Prod caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start php-fpm
exec php-fpm
