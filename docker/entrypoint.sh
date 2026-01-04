#!/bin/bash
set -e

echo "🚀 Starting Laravel application..."

# Create storage directories if they don't exist
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/log/supervisor

# Set permissions
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Generate application key if not set
if [ -z "$APP_KEY" ]; then
    echo "⚠️  APP_KEY not set, generating new key..."
    php artisan key:generate --force
fi

# Clear and cache configuration
echo "📦 Caching configuration..."
php artisan config:clear
php artisan config:cache

# Cache routes
echo "🛤️  Caching routes..."
php artisan route:cache

# Cache views
echo "👁️  Caching views..."
php artisan view:cache

# Run migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Seed database if empty (only on first deploy)
if [ "$SEED_DATABASE" = "true" ]; then
    echo "🌱 Seeding database..."
    php artisan db:seed --force
fi

# Create storage link
echo "🔗 Creating storage link..."
php artisan storage:link --force || true

echo "✅ Laravel application ready!"

# Start supervisor (manages nginx + php-fpm)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

