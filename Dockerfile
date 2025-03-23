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

# Enable Apache rewrite module
RUN a2enmod rewrite

# Add this after the Apache rewrite module is enabled
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

# Create public directory for web root
RUN mkdir -p public config

# Copy Composer files first for dependency installation
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader || composer update --no-dev --optimize-autoloader

# Copy application code
COPY . .

# Create a script to update Apache config at runtime
RUN echo '#!/bin/bash\n\
# Configure Apache port dynamically\n\
PORT="${PORT:-8080}"\n\
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf\n\
sed -i "s/<VirtualHost \\*:80>/<VirtualHost *:$PORT>/g" /etc/apache2/sites-available/000-default.conf\n\
\n\
# Configure document root to public directory\n\
sed -i "s|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|" /etc/apache2/sites-available/000-default.conf\n\
\n\
# Execute Apache\n\
exec apache2-foreground\n\
' > /usr/local/bin/start-apache.sh

# Make the script executable
RUN chmod +x /usr/local/bin/start-apache.sh

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose the port
EXPOSE 8080

# Start Apache with our custom script
CMD ["/usr/local/bin/start-apache.sh"]
