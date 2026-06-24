#!/bin/bash
set -euo pipefail

DIR_OPENCART="${DIR_OPENCART:-/var/www/html/}"
DIR_STORAGE="${DIR_STORAGE:-/storage/}"

DB_HOSTNAME="${DB_HOSTNAME:-${MYSQLHOST:-}}"
DB_USERNAME="${DB_USERNAME:-${MYSQLUSER:-root}}"
DB_PASSWORD="${DB_PASSWORD:-${MYSQLPASSWORD:-}}"
DB_DATABASE="${DB_DATABASE:-${MYSQLDATABASE:-railway}}"
DB_PORT="${DB_PORT:-${MYSQLPORT:-3306}}"
DB_PREFIX="${DB_PREFIX:-ws_}"

HTTP_HOST="${RAILWAY_PUBLIC_DOMAIN:-${OPENCART_HTTP_HOST:-localhost}}"
HTTP_SCHEME="${OPENCART_HTTP_SCHEME:-https}"
HTTP_SERVER="${HTTP_SCHEME}://${HTTP_HOST}/"
HTTP_ADMIN="${HTTP_SCHEME}://${HTTP_HOST}/admin/"

PORT="${PORT:-80}"

configure_apache_port() {
  echo "Apache na porta ${PORT}"
  sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
  sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
}

write_catalog_config() {
  cat > "${DIR_OPENCART}config.php" <<PHP
<?php
define('APPLICATION', 'Catalog');

define('HTTP_SERVER', '${HTTP_SERVER}');

define('DIR_OPENCART', '${DIR_OPENCART}');
define('DIR_APPLICATION', DIR_OPENCART . 'catalog/');
define('DIR_EXTENSION', DIR_OPENCART . 'extension/');
define('DIR_IMAGE', DIR_OPENCART . 'image/');
define('DIR_SYSTEM', DIR_OPENCART . 'system/');
define('DIR_STORAGE', '${DIR_STORAGE}');
define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
define('DIR_LOGS', DIR_STORAGE . 'logs/');
define('DIR_SESSION', DIR_STORAGE . 'session/');
define('DIR_UPLOAD', DIR_STORAGE . 'upload/');

define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', '${DB_HOSTNAME}');
define('DB_USERNAME', '${DB_USERNAME}');
define('DB_PASSWORD', '${DB_PASSWORD}');
define('DB_DATABASE', '${DB_DATABASE}');
define('DB_PORT', '${DB_PORT}');
define('DB_PREFIX', '${DB_PREFIX}');
define('DB_SSL_KEY', '');
define('DB_SSL_CERT', '');
define('DB_SSL_CA', '');
PHP
}

write_admin_config() {
  cat > "${DIR_OPENCART}admin/config.php" <<PHP
<?php
define('APPLICATION', 'Admin');

define('HTTP_SERVER', '${HTTP_ADMIN}');
define('HTTP_CATALOG', '${HTTP_SERVER}');

define('DIR_OPENCART', '${DIR_OPENCART}');
define('DIR_APPLICATION', DIR_OPENCART . 'admin/');
define('DIR_EXTENSION', DIR_OPENCART . 'extension/');
define('DIR_IMAGE', DIR_OPENCART . 'image/');
define('DIR_SYSTEM', DIR_OPENCART . 'system/');
define('DIR_CATALOG', DIR_OPENCART . 'catalog/');
define('DIR_STORAGE', '${DIR_STORAGE}');
define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
define('DIR_LOGS', DIR_STORAGE . 'logs/');
define('DIR_SESSION', DIR_STORAGE . 'session/');
define('DIR_UPLOAD', DIR_STORAGE . 'upload/');

define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', '${DB_HOSTNAME}');
define('DB_USERNAME', '${DB_USERNAME}');
define('DB_PASSWORD', '${DB_PASSWORD}');
define('DB_DATABASE', '${DB_DATABASE}');
define('DB_PORT', '${DB_PORT}');
define('DB_PREFIX', '${DB_PREFIX}');
define('DB_SSL_KEY', '');
define('DB_SSL_CERT', '');
define('DB_SSL_CA', '');

define('OPENCART_SERVER', 'https://www.opencart.com/');
PHP
}

mkdir -p "${DIR_STORAGE}"{cache,session,logs,download,upload,backup,marketplace}
chown -R www-data:www-data "${DIR_STORAGE}"
chmod -R 775 "${DIR_STORAGE}"

configure_apache_port

if [ -n "${RAILWAY_ENVIRONMENT:-}" ] || [ -n "${MYSQLHOST:-}" ] || [ ! -f "${DIR_OPENCART}config.php" ]; then
  write_catalog_config
  write_admin_config
fi

exec apache2-foreground
