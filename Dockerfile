FROM php:8.2-apache

# System dependencies
RUN apt-get update && apt-get install -y \
    git unzip zip libpq-dev curl \
    && docker-php-ext-install pdo pdo_pgsql

# Apache rewrite
RUN a2enmod rewrite

WORKDIR /var/www/html

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ✅ FIX: nur composer.json kopieren
COPY composer.json ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Rest vom Projekt
COPY . .

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80