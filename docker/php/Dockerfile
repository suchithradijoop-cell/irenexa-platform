FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    libsqlite3-dev \
    sqlite3 \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        pdo_sqlite \
        mbstring \
        bcmath \
        exif \
        gd \
        intl \
        zip \
        opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

RUN mkdir -p /var/www/storage/framework/views /var/www/bootstrap/cache /var/www/database \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/database \
    && touch /var/www/database/database.sqlite 2>/dev/null || true \
    && chown www-data:www-data /var/www/database/database.sqlite 2>/dev/null || true
