# استفاده از PHP 8.2 همراه Apache
FROM php:8.2-apache

# کپی کردن کل پروژه به دایرکتوری وب
COPY . /var/www/html/

# نصب اکستنشن‌های مورد نیاز
RUN docker-php-ext-install mysqli pdo pdo_mysql

# فعال کردن mod_rewrite در Apache
RUN a2enmod rewrite
