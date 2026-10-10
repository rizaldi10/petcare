FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql mysqli \
    && a2enmod rewrite

WORKDIR /var/www/html
COPY . /var/www/html
RUN chmod +x /var/www/html/docker-entrypoint.sh \
    && chown -R www-data:www-data /var/www/html

CMD ["/var/www/html/docker-entrypoint.sh"]
