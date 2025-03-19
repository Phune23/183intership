# Sử dụng PHP 8.4.5 với Apache
FROM php:8.4.5-apache
FROM php:apache
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Cài đặt Composer & PHP Extensions
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    zip \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    && docker-php-ext-install mysqli pdo_mysql zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Kiểm tra Composer đã cài thành công chưa
RUN composer --version

# Bật module rewrite của Apache
RUN a2enmod rewrite

# Thiết lập thư mục làm việc
WORKDIR /var/www/html

# Sao chép trước composer.json và composer.lock để cache dependencies (tăng tốc build)
COPY composer.json composer.lock /var/www/html/

# Cài đặt dependencies trước khi copy toàn bộ mã nguồn (giúp tận dụng cache)
RUN composer install --no-dev --optimize-autoloader || composer update --no-dev --optimize-autoloader

# Sao chép toàn bộ mã nguồn vào container
COPY . /var/www/html/

# Cấp quyền cho thư mục dự án
RUN chown -R www-data:www-data /var/www/html && chmod -R 775 /var/www/html

# Cấu hình Apache để hỗ trợ .htaccess và index.php mặc định
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
    DirectoryIndex index.php\n\
</Directory>' > /etc/apache2/conf-available/docker-php.conf \
    && a2enconf docker-php

# Sửa lỗi "Could not reliably determine the server's fully qualified domain name"
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Cấu hình Apache để sử dụng PORT từ Railway
RUN echo '#!/bin/bash\n\
sed -i "s/Listen 80/Listen ${PORT:-8080}/" /etc/apache2/ports.conf\n\
sed -i "s/<VirtualHost \\*:80>/<VirtualHost \\*:${PORT:-8080}>/" /etc/apache2/sites-available/000-default.conf\n\
exec "$@"' > /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

# Sử dụng ENTRYPOINT để khởi tạo cấu hình động
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

# Expose cổng (sẽ được ghi đè bởi PORT của Railway)
EXPOSE 8080

# Chạy Apache khi container khởi động
CMD ["apachectl", "-D", "FOREGROUND"]

