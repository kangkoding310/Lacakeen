FROM php:8.4-fpm-alpine

LABEL maintainer="Lacakeen Team"

# Install system dependencies
RUN apk update && apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    oniguruma-dev \
    linux-headers \
    nodejs \
    npm \
    supervisor \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    pgsql \
    mbstring \
    zip \
    exif \
    pcntl \
    bcmath \
    gd \
    opcache

# Install Redis extension
RUN pecl install redis && \
    docker-php-ext-enable redis

# Install Xdebug (for local development)
RUN pecl install xdebug && \
    docker-php-ext-enable xdebug

# Clear cache
RUN apk del $PHPIZE_DEPS && rm -rf /var/cache/apk/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy custom PHP config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Copy entrypoint
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Create laravel user
RUN addgroup -g 1000 -S laravel && \
    adduser -u 1000 -S laravel -G laravel

# Set permissions
RUN chown -R laravel:laravel /var/www/html

USER laravel

ENTRYPOINT ["entrypoint.sh"]
