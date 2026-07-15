FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
        libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libonig-dev unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql mysqli gd zip mbstring \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader || true

COPY . .
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader

RUN sed -ri 's/DocumentRoot .*/DocumentRoot \/var\/www\/html/' /etc/apache2/sites-available/*.conf \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/logs

EXPOSE 80
