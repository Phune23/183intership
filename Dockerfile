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

# Copy Composer files first for cache
COPY composer.json composer.lock /var/www/html/

# Install dependencies
RUN composer install --no-dev --optimize-autoloader || composer update --no-dev --optimize-autoloader

# Copy all source code to container
COPY . /var/www/html/

# Set permissions for the project directory
RUN chown -R www-data:www-data /var/www/html && chmod -R 775 /var/www/html

# Configure Apache to use index.php as default
RUN sed -i 's|DirectoryIndex .*|DirectoryIndex index.php index.html|' /etc/apache2/mods-enabled/dir.conf

# Configure Apache ports for Railway
RUN sed -i "s/Listen 80/Listen ${PORT:-8080}/" /etc/apache2/ports.conf \
    && sed -i "s/<VirtualHost \\*:80>/<VirtualHost \\*:${PORT:-8080}>/" /etc/apache2/sites-available/000-default.conf

# Update Apache document root to use the public directory
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf

# Expose port
EXPOSE 8080

# Start Apache
CMD ["apache2-foreground"]
