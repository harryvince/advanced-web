# Dockerfile
FROM php:7.4-apache

COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
COPY start-apache.sh /usr/local/bin
RUN a2enmod rewrite

# Install MySql Driver for PDO
RUN docker-php-ext-install pdo pdo_mysql

# Copy Application Source
COPY src /var/www
RUN chown -R www-data:www-data /var/www

CMD [ "start-apache.sh" ]