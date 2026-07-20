#!/bin/bash
set -euo pipefail

DIR_OPENCART="${DIR_OPENCART:-/var/www/html/}"
DIR_STORAGE="${DIR_STORAGE:-/storage/}"

echo "[local] MIIGTOOLS OpenCart — bootstrap"

mkdir -p "${DIR_STORAGE}"{cache,session,logs,download,upload,backup,marketplace}
mkdir -p "${DIR_OPENCART}image/cache"

# SEO rewrite rules (OpenCart ships .htaccess.txt; real .htaccess is gitignored)
if [ ! -f "${DIR_OPENCART}.htaccess" ] && [ -f "${DIR_OPENCART}.htaccess.txt" ]; then
	cp "${DIR_OPENCART}.htaccess.txt" "${DIR_OPENCART}.htaccess"
	echo "[local] .htaccess gerado a partir de .htaccess.txt"
fi

touch "${DIR_OPENCART}config.php" "${DIR_OPENCART}admin/config.php"
chmod 666 "${DIR_OPENCART}config.php" "${DIR_OPENCART}admin/config.php" || true
chown -R www-data:www-data "${DIR_STORAGE}" "${DIR_OPENCART}image/cache" 2>/dev/null || true

if [ ! -f "${DIR_OPENCART}install.lock" ]; then
	echo "[local] Aguardando MySQL..."
	wait-for-it mysql:3306 -t 120

	echo "[local] Instalando OpenCart (CLI)..."
	php "${DIR_OPENCART}install/cli_install.php" install \
		--username admin \
		--password admin \
		--email admin@miigtools.local \
		--http_server "http://localhost:8888/" \
		--db_driver mysqli \
		--db_hostname mysql \
		--db_username root \
		--db_password opencart \
		--db_database opencart \
		--db_port 3306 \
		--db_prefix ws_

	php /usr/local/bin/write-config.php
	touch "${DIR_OPENCART}install.lock"
	echo "[local] Instalação concluída (admin / admin)."
else
	echo "[local] Já instalado — regenerando config.php"
	php /usr/local/bin/write-config.php
fi

exec apache2-foreground
