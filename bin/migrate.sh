#!/bin/sh
set -eu

if [ -z "${DB_HOST:-}" ]; then
    exit 0
fi

db_port="${DB_PORT:-3306}"
db_name="${DB_NAME:?DB_NAME is required when DB_HOST is set}"
db_user="${DB_USER:?DB_USER is required when DB_HOST is set}"
db_password="${DB_PASSWORD:?DB_PASSWORD is required when DB_HOST is set}"
app_root="${BKK_APP_ROOT:-/var/www}"

mysql_query() {
    MYSQL_PWD="$db_password" mysql --protocol=TCP --host="$DB_HOST" --port="$db_port" \
        --user="$db_user" --database="$db_name" --default-character-set=utf8mb4 \
        --batch --skip-column-names --execute="$1"
}

if [ "${RUN_DATABASE_INITIALIZATION:-false}" = "true" ]; then
    table_count="$(mysql_query "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'users'")"
    if [ "$table_count" = "0" ]; then
        MYSQL_PWD="$db_password" mysql --protocol=TCP --host="$DB_HOST" --port="$db_port" \
            --user="$db_user" --database="$db_name" --default-character-set=utf8mb4 < "$app_root/database/schema.sql"
        MYSQL_PWD="$db_password" mysql --protocol=TCP --host="$DB_HOST" --port="$db_port" \
            --user="$db_user" --database="$db_name" --default-character-set=utf8mb4 < "$app_root/database/seed.sql"
    fi
fi

mysql_query "CREATE TABLE IF NOT EXISTS schema_migrations (
    migration_name VARCHAR(190) PRIMARY KEY,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
)" >/dev/null

for migration_file in "$app_root"/database/migrations/*.sql; do
    migration_name="$(basename "$migration_file")"
    applied="$(mysql_query "SELECT COUNT(*) FROM schema_migrations WHERE migration_name = '${migration_name}'")"
    if [ "$applied" = "0" ]; then
        MYSQL_PWD="$db_password" mysql --protocol=TCP --host="$DB_HOST" --port="$db_port" \
            --user="$db_user" --database="$db_name" --default-character-set=utf8mb4 < "$migration_file"
        mysql_query "INSERT INTO schema_migrations (migration_name) VALUES ('${migration_name}')" >/dev/null
    fi
done
