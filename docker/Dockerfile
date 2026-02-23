FROM php:8.3-fpm-alpine

# Install dependencies for GD, Zip, PDO, and Composer
RUN apk add --no-cache \
        bash \
        libzip-dev \
        zip \
        unzip \
        freetype-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype \
        libpng \
        libjpeg-turbo \
        curl \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
    && docker-php-ext-install pdo pdo_mysql zip gd \
    && apk del $PHPIZE_DEPS

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www