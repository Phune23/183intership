# Sử dụng PHP với Apache
FROM php:8.4.5-apache

# Cài đặt MySQL và các thư viện cần thiết
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Cài đặt Composer & PHP Extensions
RUN apt-get update && apt-get install -y \
    curl unzip zip libzip-dev libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && docker-php-ext-install mysqli pdo_mysql zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Kiểm tra Composer
RUN composer --version

# Bật module rewrite của Apache
RUN a2enmod rewrite

# Thiết lập thư mục làm việc
WORKDIR /var/www/html

# Sao chép file Composer trước để cache
COPY composer.json composer.lock /var/www/html/

# Cài đặt dependencies
RUN composer install --no-dev --optimize-autoloader || composer update --no-dev --optimize-autoloader

# Sao chép toàn bộ mã nguồn vào container
COPY . /var/www/html/

# Cấp quyền cho thư mục dự án
RUN chown -R www-data:www-data /var/www/html && chmod -R 775 /var/www/html

# Sửa lỗi không nhận index.php
RUN sed -i 's|DirectoryIndex .*|DirectoryIndex index.php index.html|' /etc/apache2/mods-enabled/dir.conf

# Chỉnh cổng Apache để nhận từ Railway
RUN sed -i "s/Listen 80/Listen ${PORT:-8080}/" /etc/apache2/ports.conf \
    && sed -i "s/<VirtualHost \\*:80>/<VirtualHost \\*:${PORT:-8080}>/" /etc/apache2/sites-available/000-default.conf

# Expose cổng (sẽ được Railway tự động gán)
EXPOSE 8080

# Chạy Apache
CMD ["sh", "-c", "apachectl -D FOREGROUND"]
