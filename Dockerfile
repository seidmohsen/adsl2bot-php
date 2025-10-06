# استفاده از PHP 8.2 همراه Apache

FROM php:8.2-apache

# کپی کل پروژه به دایرکتوری وب
COPY . /var/www/html/

# نصب اکستنشن‌های PDO و PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# فعال کردن mod_rewrite در Apache
RUN a2enmod rewrite
