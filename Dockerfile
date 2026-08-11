FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends default-mysql-client libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && rm -rf /var/lib/apt/lists/*

COPY app /var/www/app
COPY database /var/www/database
COPY public /var/www/html
COPY .env.example /var/www/.env.example

RUN chown -R www-data:www-data /var/www/app /var/www/database /var/www/html

EXPOSE 8080

CMD ["sh", "-c", "set -eu; if [ \"${RUN_DATABASE_INITIALIZATION:-false}\" = \"true\" ]; then BKK_TABLE_COUNT=\"$(MYSQL_PWD=\"$DB_PASSWORD\" mysql --protocol=TCP --host=\"$DB_HOST\" --port=\"$DB_PORT\" --user=\"$DB_USER\" --database=\"$DB_NAME\" --batch --skip-column-names --execute=\"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'users'\")\"; if [ \"$BKK_TABLE_COUNT\" = \"0\" ]; then MYSQL_PWD=\"$DB_PASSWORD\" mysql --protocol=TCP --host=\"$DB_HOST\" --port=\"$DB_PORT\" --user=\"$DB_USER\" --database=\"$DB_NAME\" < /var/www/database/schema.sql; MYSQL_PWD=\"$DB_PASSWORD\" mysql --protocol=TCP --host=\"$DB_HOST\" --port=\"$DB_PORT\" --user=\"$DB_USER\" --database=\"$DB_NAME\" < /var/www/database/seed.sql; fi; MYSQL_PWD=\"$DB_PASSWORD\" mysql --protocol=TCP --host=\"$DB_HOST\" --port=\"$DB_PORT\" --user=\"$DB_USER\" --database=\"$DB_NAME\" < /var/www/database/migrations/002_fix_service_hours_encoding.sql; fi; exec php -S 0.0.0.0:${PORT:-8080} -t /var/www/html /var/www/html/router.php"]
