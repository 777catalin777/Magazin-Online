FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_pgsql pdo_sqlite

COPY . /var/www/html/

RUN printf '%s\n' \
    '<Directory "/var/www/html/database">' \
    '    Require all denied' \
    '</Directory>' \
    > /etc/apache2/conf-available/maison-lure-security.conf \
    && a2enconf maison-lure-security

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
