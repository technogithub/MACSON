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

# Run composer scripts now that full app code exists
RUN composer dump-autoload --optimize --no-interaction

# Create required Laravel storage directories & set permissions (instant execution)
RUN mkdir -p storage/framework/sessions \
             storage/framework/views \
             storage/framework/cache/data \
             storage/app/public \
             storage/logs \
             bootstrap/cache \
    && touch storage/logs/laravel.log \
    && chmod -R 777 storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
