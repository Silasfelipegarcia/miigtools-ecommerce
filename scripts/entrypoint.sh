#!/bin/bash
set -eu

DIR_OPENCART="${DIR_OPENCART:-/var/www/html/}"
DIR_STORAGE="${DIR_STORAGE:-/storage/}"
PORT="${PORT:-8080}"

echo "[entrypoint] MIIGTOOLS OpenCart — porta ${PORT}"

configure_apache() {
	# Sobrescreve sempre (idempotente). Nunca use sed em Listen 80 — em 8080 vira 808080.
	cat > /etc/apache2/ports.conf <<EOF
Listen ${PORT}
EOF

	cat > /etc/apache2/sites-available/000-default.conf <<EOF
<VirtualHost *:${PORT}>
	ServerAdmin webmaster@localhost
	DocumentRoot /var/www/html

	<Directory /var/www/html>
		Options Indexes FollowSymLinks
		AllowOverride All
		Require all granted
	</Directory>

	ErrorLog \${APACHE_LOG_DIR}/error.log
	CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF
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
