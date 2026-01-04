# Dockerfile for Laravel on Render
FROM php:8.2-cli

# Set environment variables
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MEMORY_LIMIT=-1
ENV COMPOSER_NO_INTERACTION=1

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring bcmath zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Create .env file for artisan commands during build
RUN touch .env \
    && echo "APP_NAME=Laravel" >> .env \
    && echo "APP_ENV=production" >> .env \
    && echo "APP_KEY=base64:dGVtcG9yYXJ5a2V5Zm9yYnVpbGQxMjM0NTY3ODkw" >> .env \
    && echo "APP_DEBUG=false" >> .env \
    && echo "DB_CONNECTION=sqlite" >> .env \
    && echo "CACHE_STORE=array" >> .env \
    && echo "SESSION_DRIVER=array" >> .env

# Install dependencies (--ignore-platform-reqs for cross-platform compatibility)
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader --ignore-platform-reqs

# Copy application code
COPY . .

# Create storage directories
RUN mkdir -p storage/logs \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    bootstrap/cache

# Set permissions
RUN chmod -R 775 storage bootstrap/cache

# Run post-install scripts
RUN composer dump-autoload --optimize --ignore-platform-reqs || true

# Remove build .env
RUN rm -f .env

# Expose port
EXPOSE 10000

# Copy and set entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

CMD ["/entrypoint.sh"]
