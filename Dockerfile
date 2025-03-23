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
COPY composer.json composer.lock* ./

# Install dependencies
RUN composer install --no-scripts --no-autoloader || echo "Skipping composer install"

# Copy application code
COPY . .

# Final composer install with autoloader optimization
RUN composer dump-autoload --optimize --no-dev || echo "Skipping autoloader optimization"

# Cấu hình file VirtualHost đơn giản
RUN echo '<VirtualHost *:${PORT:-80}>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/public\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
    <Directory /var/www/html/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Tạo script khởi động đơn giản hơn
RUN echo '#!/bin/bash\n\
# Lấy cổng từ biến môi trường hoặc dùng 80 nếu không có\n\
PORT="${PORT:-80}"\n\
\n\
# Cấu hình port.conf\n\
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf\n\
\n\
# Cấu hình VirtualHost\n\
sed -i "s/\\${PORT:-80}/$PORT/g" /etc/apache2/sites-available/000-default.conf\n\
\n\
# Kiểm tra cấu hình\n\
echo "Cấu hình Apache:"\n\
apache2 -t\n\
\n\
# Khởi động Apache\n\
exec apache2-foreground\n\
' > /usr/local/bin/start-apache.sh

RUN chmod +x /usr/local/bin/start-apache.sh

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose the port
EXPOSE 80

# Start Apache with our custom script
CMD ["/usr/local/bin/start-apache.sh"]
