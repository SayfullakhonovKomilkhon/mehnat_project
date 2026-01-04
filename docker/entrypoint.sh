#!/bin/bash
set -e

cd /var/www/html

echo "============================================"
echo "=== Starting Laravel Application ==="
echo "============================================"

# Create storage directories
echo "[1/8] Creating storage directories..."
mkdir -p storage/logs storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache
chmod -R 775 storage bootstrap/cache
echo "     Done!"

# Wait for database
if [ -n "$DATABASE_URL" ]; then
    echo "[2/8] Waiting for database connection..."
    sleep 3
    echo "     Done!"
else
    echo "[2/8] No DATABASE_URL set, skipping wait..."
fi

# Clear cache
echo "[3/8] Clearing cache..."
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
echo "     Done!"

# Run migrations
echo "[4/8] Running migrations..."
php artisan migrate --force --verbose
echo "     Migrations complete!"

# Check SEED_DATABASE variable
echo "[5/8] Checking SEED_DATABASE variable..."
echo "     SEED_DATABASE = '$SEED_DATABASE'"

# Seed database
if [ "$SEED_DATABASE" = "true" ] || [ "$SEED_DATABASE" = "1" ] || [ "$SEED_DATABASE" = "yes" ]; then
    echo "[6/8] Seeding database (SEED_DATABASE=$SEED_DATABASE)..."
    php artisan db:seed --force --verbose
    echo "     Seeding complete!"
else
    echo "[6/8] Skipping seeding (SEED_DATABASE is not 'true')"
    echo "     To enable seeding, set SEED_DATABASE=true in environment"
fi

# Cache config for production
echo "[7/8] Caching configuration..."
php artisan config:cache || echo "     Config cache failed, continuing..."
php artisan route:cache || echo "     Route cache failed, continuing..."
echo "     Done!"

# Storage link
echo "[8/8] Creating storage link..."
php artisan storage:link 2>/dev/null || true
echo "     Done!"

echo "============================================"
echo "=== Laravel Application Ready! ==="
echo "=== Starting PHP Server on port 10000 ==="
echo "============================================"

# Start PHP built-in server
exec php artisan serve --host=0.0.0.0 --port=10000
