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

# Kiểm tra xem file cấu hình Apache ở đâu
RUN ls -la /etc/apache2/sites-available/ || echo "Thư mục sites-available không tồn tại"
RUN find /etc/apache2 -name "*.conf" | sort

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

# Tạo script khởi động thông minh hơn để tìm và cấu hình file Apache
RUN echo '#!/bin/bash\n\
# Thiết lập cổng mặc định \n\
PORT="${PORT:-8080}"\n\
\n\
# Cấu hình ports.conf nếu tồn tại\n\
if [ -f /etc/apache2/ports.conf ]; then\n\
    echo "Đã tìm thấy /etc/apache2/ports.conf - đang cấu hình cổng $PORT"\n\
    sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf\n\
else\n\
    echo "Không tìm thấy /etc/apache2/ports.conf"\n\
fi\n\
\n\
# Tìm và cấu hình tất cả các file VirtualHost\n\
VHOST_FILES=$(find /etc/apache2 -name "*.conf" -exec grep -l "VirtualHost" {} \;)\n\
if [ -n "$VHOST_FILES" ]; then\n\
    for file in $VHOST_FILES; do\n\
        echo "Đang cập nhật VirtualHost trong $file"\n\
        sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/g" $file\n\
    done\n\
else\n\
    echo "Không tìm thấy file VirtualHost nào"\n\
fi\n\
\n\
# Cấu hình document root là public\n\
VHOST_FILES=$(find /etc/apache2 -name "*.conf" -exec grep -l "DocumentRoot" {} \;)\n\
if [ -n "$VHOST_FILES" ]; then\n\
    for file in $VHOST_FILES; do\n\
        echo "Đang cấu hình DocumentRoot trong $file"\n\
        sed -i "s|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g" $file\n\
    done\n\
else\n\
    echo "Không tìm thấy file DocumentRoot nào"\n\
fi\n\
\n\
# Kiểm tra Apache\n\
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
EXPOSE 8080

# Start Apache with our custom script
CMD ["/usr/local/bin/start-apache.sh"]
