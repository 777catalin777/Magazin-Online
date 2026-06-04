FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_pgsql pdo_sqlite

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80