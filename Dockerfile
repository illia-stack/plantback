# PHP + Apache Image
FROM php:8.2-apache

# System dependencies
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql mysqli zip

# Apache mod rewrite aktivieren
RUN a2enmod rewrite

# Arbeitsverzeichnis
WORKDIR /var/www/html

# Dateien kopieren
COPY . /var/www/html/

# Composer installieren
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Dependencies installieren
RUN composer install --no-dev --optimize-autoloader || true

# Rechte setzen
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80