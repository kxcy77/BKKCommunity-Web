FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && a2enmod headers rewrite \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && rm -rf /var/lib/apt/lists/*

COPY app /var/www/app
COPY public /var/www/html
COPY .env.example /var/www/.env.example

RUN chown -R www-data:www-data /var/www/app /var/www/html

EXPOSE 10000

CMD ["sh", "-c", "sed -ri \"s/Listen 80/Listen ${PORT:-10000}/\" /etc/apache2/ports.conf && sed -ri \"s/<VirtualHost \\*:80>/<VirtualHost *:${PORT:-10000}>/\" /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
