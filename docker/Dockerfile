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

# Pre-install Composer Dependencies during image build phase
COPY backend-laravel/composer.json /var/www/html/
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts 2>/dev/null || true

# Copy full backend application code
COPY backend-laravel/ /var/www/html/
RUN composer dump-autoload --optimize 2>/dev/null || true

# Set Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
