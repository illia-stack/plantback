FROM php:8.2-apache

# System dependencies
RUN apt-get update && apt-get install -y \
    git unzip zip libpq-dev curl \
    && docker-php-ext-install pdo pdo_pgsql

# Apache rewrite (for React routing / API clean URLs)
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Install Composer FIRST
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy ONLY composer files first (better caching)
COPY composer.json composer.lock ./

# Install PHP dependencies (THIS is where PHPMailer gets installed)
RUN composer install --no-dev --optimize-autoloader

# Now copy the rest of your backend
COPY . .

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80