FROM php:8.2-fpm

# Installeer afhankelijkheden
RUN apt-get update && apt-get install -y \
    libicu-dev libpng-dev libzip-dev libonig-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl mysqli gd zip mbstring

# Redis extensie
RUN pecl install redis && docker-php-ext-enable redis

WORKDIR /var/www/html

# Zet rechten voor CI4
RUN chown -R www-data:www-data /var/www/html

# Onderaan je Dockerfile toevoegen
RUN mkdir -p /var/www/html/writable/cache /var/www/html/writable/logs /var/www/html/writable/session /var/www/html/writable/debugbar \
    && chown -R www-data:www-data /var/www/html/writable \
    && chmod -R 775 /var/www/html/writable
