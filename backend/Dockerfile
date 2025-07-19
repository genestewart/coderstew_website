FROM php:8.3-fpm

# Store composer files inside the project directory
ENV COMPOSER_HOME=/var/www/html/.composer \
    COMPOSER_CACHE_DIR=/var/www/html/.composer/cache

RUN mkdir -p "$COMPOSER_CACHE_DIR"

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    curl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader || true

COPY . .

CMD ["php-fpm"]
