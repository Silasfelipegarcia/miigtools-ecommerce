#!/bin/bash
# update-store-url.sh
#
# Updates the OpenCart store URL in the database so that CSS, JS, and image
# resources are served from the correct Railway public domain instead of
# localhost.
#
# Called automatically by entrypoint.sh after write-config.php runs.
# Can also be run manually:
#   bash /usr/local/bin/update-store-url.sh
#
# Required environment variables (same ones used by write-config.php):
#   DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE
# Optional:
#   DB_PORT      (default: 3306)
#   DB_PREFIX    (default: ws_)
#   RAILWAY_PUBLIC_DOMAIN / OPENCART_HTTP_HOST / OPENCART_HTTP_SCHEME

set -euo pipefail

DB_HOST="${DB_HOSTNAME:-${MYSQLHOST:-}}"
DB_USER="${DB_USERNAME:-${MYSQLUSER:-root}}"
DB_PASS="${DB_PASSWORD:-${MYSQLPASSWORD:-}}"
DB_NAME="${DB_DATABASE:-${MYSQLDATABASE:-railway}}"
DB_PORT="${DB_PORT:-${MYSQLPORT:-3306}}"
DB_PREFIX="${DB_PREFIX:-ws_}"

HTTP_HOST="${RAILWAY_PUBLIC_DOMAIN:-${OPENCART_HTTP_HOST:-localhost}}"
HTTP_SCHEME="${OPENCART_HTTP_SCHEME:-https}"
STORE_URL="${HTTP_SCHEME}://${HTTP_HOST}/"

TABLE="${DB_PREFIX}setting"

if [ -z "$DB_HOST" ]; then
  echo "[update-store-url] DB_HOSTNAME não definido — pulando atualização do banco."
  exit 0
fi

echo "[update-store-url] Aguardando MySQL em ${DB_HOST}:${DB_PORT}..."

# Wait up to 30 s for MySQL to accept connections before giving up gracefully.
RETRIES=15
until mysql \
    --host="$DB_HOST" \
    --port="$DB_PORT" \
    --user="$DB_USER" \
    --password="$DB_PASS" \
    --database="$DB_NAME" \
    --connect-timeout=2 \
    -e "SELECT 1;" >/dev/null 2>&1; do
  RETRIES=$((RETRIES - 1))
  if [ "$RETRIES" -le 0 ]; then
    echo "[update-store-url] MySQL não respondeu a tempo — pulando atualização."
    exit 0
  fi
  echo "[update-store-url] MySQL ainda não disponível, tentando novamente em 2 s... (${RETRIES} tentativas restantes)"
  sleep 2
done

echo "[update-store-url] Atualizando config_url e config_secure → ${STORE_URL}"

mysql \
  --host="$DB_HOST" \
  --port="$DB_PORT" \
  --user="$DB_USER" \
  --password="$DB_PASS" \
  --database="$DB_NAME" \
  <<SQL
UPDATE \`${TABLE}\`
SET    \`value\` = '${STORE_URL}'
WHERE  \`key\`   IN ('config_url', 'config_secure')
  AND  \`store_id\` = 0;
SQL

echo "[update-store-url] Concluído."
