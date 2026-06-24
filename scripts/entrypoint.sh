#!/bin/bash
set -eu

DIR_OPENCART="${DIR_OPENCART:-/var/www/html/}"
DIR_STORAGE="${DIR_STORAGE:-/storage/}"
PORT="${PORT:-8080}"

echo "[entrypoint] MIIGTOOLS OpenCart — porta ${PORT}"

ensure_single_mpm() {
	rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf
	rm -f /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf
	if [ ! -e /etc/apache2/mods-enabled/mpm_prefork.load ]; then
		a2enmod mpm_prefork
	fi
}

configure_apache() {
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

	a2ensite 000-default.conf 2>/dev/null || true
}

mkdir -p "${DIR_STORAGE}"{cache,session,logs,download,upload,backup,marketplace}
chown -R www-data:www-data "${DIR_STORAGE}" || true
chmod -R 775 "${DIR_STORAGE}" || true

ensure_single_mpm
configure_apache

if [ -n "${RAILWAY_ENVIRONMENT:-}" ] || [ -n "${MYSQLHOST:-}" ] || [ ! -f "${DIR_OPENCART}config.php" ]; then
	echo "[entrypoint] Gerando config.php..."
	php /usr/local/bin/write-config.php
fi

echo "[entrypoint] Validando Apache..."
apache2ctl configtest

echo "[entrypoint] Iniciando Apache..."
exec apache2-foreground
