FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git unzip zip libpq-dev curl \
    && docker-php-ext-install pdo pdo_pgsql

RUN a2enmod rewrite

WORKDIR /var/www/html

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 👉 erst composer.json + lock
COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader

# 👉 dann Code
COPY . .

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80