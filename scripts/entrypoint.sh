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
	a2enmod setenvif rewrite 2>/dev/null || true

	cat > /etc/apache2/ports.conf <<EOF
Listen ${PORT}
EOF

	cat > /etc/apache2/sites-available/000-default.conf <<EOF
<VirtualHost *:${PORT}>
	ServerAdmin webmaster@localhost
	DocumentRoot /var/www/html
	UseCanonicalPhysicalPort Off

	# Railway termina TLS no proxy; evita redirect para http://host:8080/...
	SetEnvIf X-Forwarded-Proto "https" HTTPS=on

	# /admin sem barra → Apache gerava http://host:8080/admin/ (timeout no Railway)
	RedirectMatch 301 ^/admin$ /admin/

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

# Update config_url / config_secure in the database so resources load from the
# correct public domain. write-config.php already attempts this via PHP/mysqli;
# the shell script below is a belt-and-suspenders fallback that also waits for
# MySQL to be ready before proceeding.
if [ -n "${RAILWAY_ENVIRONMENT:-}" ] || [ -n "${MYSQLHOST:-}" ] || [ -n "${DB_HOSTNAME:-}" ]; then
	echo "[entrypoint] Atualizando URL da loja no banco de dados..."
	bash /usr/local/bin/update-store-url.sh || echo "[entrypoint] update-store-url.sh falhou (não fatal)."
fi

echo "[entrypoint] Validando Apache..."
apache2ctl configtest

echo "[entrypoint] Iniciando Apache..."
exec apache2-foreground
