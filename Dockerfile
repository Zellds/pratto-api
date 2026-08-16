FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
        libpq-dev \
        libjpeg-dev \
        libpng-dev \
        libwebp-dev \
        libfreetype6-dev \
        unzip git \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install pdo pdo_pgsql gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
