#!/bin/bash
set -eu

DIR_OPENCART="${DIR_OPENCART:-/var/www/html/}"
DIR_STORAGE="${DIR_STORAGE:-/storage/}"
PORT="${PORT:-8080}"

echo "[entrypoint] MIIGTOOLS OpenCart — porta ${PORT}"

configure_apache() {
	echo "ServerName localhost" >> /etc/apache2/apache2.conf
	sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
	sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
}

mkdir -p "${DIR_STORAGE}"{cache,session,logs,download,upload,backup,marketplace}
chown -R www-data:www-data "${DIR_STORAGE}" || true
chmod -R 775 "${DIR_STORAGE}" || true

configure_apache

if [ -n "${RAILWAY_ENVIRONMENT:-}" ] || [ -n "${MYSQLHOST:-}" ] || [ ! -f "${DIR_OPENCART}config.php" ]; then
	echo "[entrypoint] Gerando config.php..."
	php /usr/local/bin/write-config.php
fi

echo "[entrypoint] Iniciando Apache..."
exec apache2-foreground
