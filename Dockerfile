# Use a valid PHP version with Apache
FROM php:8.3-apache

# Install MySQL and required libraries
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install Composer & PHP Extensions
RUN apt-get update && apt-get install -y \
    curl unzip zip libzip-dev libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && docker-php-ext-install mysqli pdo_mysql zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Check Composer
RUN composer --version

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Create necessary directories
RUN mkdir -p public config

# Copy Composer files first for cache
COPY composer.json composer.lock /var/www/html/

# Install dependencies
RUN composer install --no-dev --optimize-autoloader || composer update --no-dev --optimize-autoloader

# Copy application code
COPY . /var/www/html/

# Ensure config directory exists and is properly populated
RUN if [ ! -d "/var/www/html/config" ]; then mkdir -p /var/www/html/config; fi

# Configure Apache to use the public directory
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Configure to use environment variables for port
ENV PORT=8080
EXPOSE 8080

# Configure Apache to use PORT environment variable
RUN sed -i 's/80/${PORT:-8080}/g' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's/Listen 80/Listen ${PORT:-8080}/g' /etc/apache2/ports.conf

# Start Apache
CMD apache2-foreground
