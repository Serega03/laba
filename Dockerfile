FROM php:7.4-apache

# Устанавливаем расширения для работы с MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

ENV MySQL HOST=mysql.railway.internal \
    MYSQL_USER=root \
    MYSQL_PASSWORD=YcEjpyfnSmmOwkfXnWpZXpmvOzSUrIKv  \
    MYSQL_DATABASE=railway

# Копируем PHP-файл внутрь контейнера
COPY index.php /var/www/html/
COPY ./style.css /var/www/html/style.css

EXPOSE 8080