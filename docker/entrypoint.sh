#!/bin/bash
set -e

echo "🚀 Starting Laravel application..."

# Create storage directories if they don't exist
echo "📁 Creating storage directories..."
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/log/supervisor

# Set permissions
echo "🔐 Setting permissions..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Wait for database to be ready (if DATABASE_URL is set)
if [ -n "$DATABASE_URL" ]; then
    echo "⏳ Waiting for database connection..."
    max_tries=30
    counter=0
    until php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; do
        counter=$((counter + 1))
        if [ $counter -gt $max_tries ]; then
            echo "❌ Could not connect to database after $max_tries attempts"
            break
        fi
        echo "   Attempt $counter/$max_tries - waiting for database..."
        sleep 2
    done
    echo "✅ Database connection established!"
fi

# Clear old cache
echo "🧹 Clearing old cache..."
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

# Cache configuration for production
echo "📦 Caching configuration..."
php artisan config:cache || echo "⚠️ Config cache failed, continuing..."

# Cache routes
echo "🛤️ Caching routes..."
php artisan route:cache || echo "⚠️ Route cache failed, continuing..."

# Cache views
echo "👁️ Caching views..."
php artisan view:cache || echo "⚠️ View cache failed, continuing..."

# Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force || echo "⚠️ Migrations failed or already up to date"

# Seed database if SEED_DATABASE is true
if [ "$SEED_DATABASE" = "true" ]; then
    echo "🌱 Seeding database..."
    php artisan db:seed --force || echo "⚠️ Seeding failed or already seeded"
fi

# Create storage link
echo "🔗 Creating storage link..."
php artisan storage:link --force 2>/dev/null || true

echo "✅ Laravel application ready!"
echo "🌐 Starting Nginx and PHP-FPM..."

# Start supervisor (manages nginx + php-fpm)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
