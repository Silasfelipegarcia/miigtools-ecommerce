#!/bin/bash
set -euo pipefail

DIR_OPENCART="${DIR_OPENCART:-/var/www/html/}"
DIR_STORAGE="${DIR_STORAGE:-/storage/}"

DB_HOSTNAME="${DB_HOSTNAME:-${MYSQLHOST:-mysql}}"
DB_USERNAME="${DB_USERNAME:-${MYSQLUSER:-root}}"
DB_PASSWORD="${DB_PASSWORD:-${MYSQLPASSWORD:-}}"
DB_DATABASE="${DB_DATABASE:-${MYSQLDATABASE:-opencart}}"
DB_PORT="${DB_PORT:-${MYSQLPORT:-3306}}"
DB_PREFIX="${DB_PREFIX:-oc_}"

HTTP_HOST="${RAILWAY_PUBLIC_DOMAIN:-${OPENCART_HTTP_HOST:-localhost}}"
HTTP_SCHEME="${OPENCART_HTTP_SCHEME:-https}"
HTTP_SERVER="${HTTP_SCHEME}://${HTTP_HOST}/"
HTTP_ADMIN="${HTTP_SCHEME}://${HTTP_HOST}/admin/"

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
PHP
}

if [ ! -f "${DIR_OPENCART}config.php" ]; then
  write_catalog_config
fi

if [ ! -f "${DIR_OPENCART}admin/config.php" ]; then
  write_admin_config
fi

exec apache2-foreground
