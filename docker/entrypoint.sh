#!/bin/bash
set -e

cd /var/www/html

echo "=== Starting Laravel Application ==="

# Create storage directories
mkdir -p storage/logs storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Wait for database
if [ -n "$DATABASE_URL" ]; then
    echo "Waiting for database..."
    sleep 5
fi

# Clear cache
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true

# Cache config
php artisan config:cache || true
php artisan route:cache || true

# Run migrations
echo "Running migrations..."
php artisan migrate --force || echo "Migration failed or already done"

# Seed if needed
if [ "$SEED_DATABASE" = "true" ]; then
    echo "Seeding database..."
    php artisan db:seed --force || echo "Seeding failed or already done"
fi

# Storage link
php artisan storage:link 2>/dev/null || true

echo "=== Starting PHP Server on port 10000 ==="

# Start PHP built-in server
exec php artisan serve --host=0.0.0.0 --port=10000
