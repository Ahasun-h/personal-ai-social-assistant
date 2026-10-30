#!/bin/sh
set -e

echo "Starting Personal AI Social Media Assistant..."

# Ensure storage directories exist
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage

# Run database migrations if auto-migrate is enabled
if [ "${AUTO_MIGRATE:-true}" = "true" ]; then
    echo "Running database migrations..."
    php /var/www/html/artisan migrate --force || true
fi

# Cache configuration in production
if [ "${APP_ENV:-production}" = "production" ]; then
    echo "Optimizing application configuration cache..."
    php /var/www/html/artisan config:cache || true
    php /var/www/html/artisan route:cache || true
    php /var/www/html/artisan view:cache || true
fi

exec "$@"
