# Simple Dockerfile for PHP + Apache (development)
FROM php:8.2-apache

# Install extensions commonly used
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
 && docker-php-ext-install mysqli pdo pdo_mysql && rm -rf /var/lib/apt/lists/*

# Enable apache mod_rewrite
RUN a2enmod rewrite

# Copy project files to webroot
WORKDIR /var/www/html
COPY . /var/www/html

# Ensure permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]
