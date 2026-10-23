# Pinned to a patched release rather than the floating 8.2 tag: CVE-2026-109618
# and CVE-2026-109619 (critical RCE, published 2026-10-21) are fixed in 8.2.35.
# A floating tag can be satisfied by a cached older base layer; a pinned one cannot.
FROM php:8.2.35-fpm-alpine

ENV COMPOSER_ALLOW_SUPERUSER=1

# Install system dependencies and PHP extensions needed by Laravel, PostgreSQL, Redis, and PDF/Excel libs.
RUN apk add --no-cache \
        bash \
        git \
        unzip \
        icu-dev \
        libzip-dev \
        libpq-dev \
        mariadb-connector-c-dev \
        oniguruma-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        freetype-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del --no-network $PHPIZE_DEPS

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

CMD ["php-fpm"]
