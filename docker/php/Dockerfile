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
    libssl-dev \
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
        opcache \
        pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis

# Separate layer on purpose: a failure here is easy to read in the build
# output, and the slow layer above stays cached.
#
# Why not a plain `pecl install mongodb` (or install-php-extensions, which
# calls pecl)? PEAR's own downloader crashes on PHP 8.3 for larger
# packages: "fread(): Argument #2 ($length) must be of type int, float
# given" in PEAR/REST.php. We bypass that downloader: fetch the tarball
# with curl, pin an explicit version, and install from the local file.
# The final `php -m | grep` fails the build immediately if the extension
# did not actually load — it can never be silently missing at runtime.
RUN curl -fsSL -o /tmp/mongodb.tgz https://pecl.php.net/get/mongodb-2.5.3.tgz \
    && yes '' | pecl install /tmp/mongodb.tgz \
    && docker-php-ext-enable mongodb \
    && rm /tmp/mongodb.tgz \
    && php -m | grep -q mongodb

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-irenexa.ini

WORKDIR /var/www

RUN mkdir -p /var/www/storage/framework/views /var/www/bootstrap/cache /var/www/database \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/database \
    && touch /var/www/database/database.sqlite 2>/dev/null || true \
    && chown www-data:www-data /var/www/database/database.sqlite 2>/dev/null || true
