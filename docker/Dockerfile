# ==============================================================================
# Laravel PHP 8.3 Application Dockerfile
# ==============================================================================

FROM php:8.3-fpm-alpine

# Install System Dependencies & Extensions
RUN apk add --no-cache \
    bash \
    icu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    mariadb-client \
    git \
    unzip \
    linux-headers

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        zip \
        opcache

# Install Redis Extension via PECL
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first (layer cache optimization)
COPY --chown=www-data:www-data backend-laravel/composer.json backend-laravel/composer.lock* ./

# Install ALL composer dependencies inside the image (vendor stays in image)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && composer clear-cache

# Copy full backend application code
COPY --chown=www-data:www-data backend-laravel/ /var/www/html/

# Ensure .env exists (copy from .env.example if not present)
RUN cp -f .env.example .env 2>/dev/null || true

# Run composer scripts now that full app code exists
RUN composer dump-autoload --optimize --no-interaction

# Create required Laravel storage directories & set permissions
RUN mkdir -p storage/framework/sessions \
             storage/framework/views \
             storage/framework/cache/data \
             storage/app/public \
             storage/logs \
             bootstrap/cache \
    && touch storage/logs/laravel.log \
    && chmod -R 777 storage bootstrap/cache

# Pre-warm config cache (will be regenerated at runtime if env differs)
RUN php artisan config:clear 2>/dev/null || true
RUN php artisan view:clear 2>/dev/null || true
RUN php artisan route:clear 2>/dev/null || true

EXPOSE 9000
CMD ["php-fpm"]
