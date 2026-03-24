FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    libzip-dev \
    libmemcached-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql zip gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/runtime \
    && chmod -R 755 /var/www/html/web/assets

RUN composer install --no-interaction --optimize-autoloader --no-dev

WORKDIR /var/www/html

CMD ["php-fpm"]