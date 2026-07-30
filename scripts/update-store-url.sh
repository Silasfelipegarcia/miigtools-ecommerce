#!/bin/bash
# update-store-url.sh
#
# Updates the OpenCart store URL in the database so that CSS, JS, and image
# resources are served from the correct public domain instead of a dead custom
# domain or localhost.
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
#   OPENCART_STORE_URL / OPENCART_ALLOW_CUSTOM_DOMAIN

set -euo pipefail

DB_HOST="${DB_HOSTNAME:-${MYSQLHOST:-}}"
DB_USER="${DB_USERNAME:-${MYSQLUSER:-root}}"
DB_PASS="${DB_PASSWORD:-${MYSQLPASSWORD:-}}"
DB_NAME="${DB_DATABASE:-${MYSQLDATABASE:-railway}}"
DB_PORT="${DB_PORT:-${MYSQLPORT:-3306}}"
DB_PREFIX="${DB_PREFIX:-ws_}"

HTTP_SCHEME="${OPENCART_HTTP_SCHEME:-https}"
ALLOW_CUSTOM="${OPENCART_ALLOW_CUSTOM_DOMAIN:-0}"
EXPLICIT_URL="${OPENCART_STORE_URL:-}"
RAILWAY_HOST="${RAILWAY_PUBLIC_DOMAIN:-}"
CUSTOM_HOST="${OPENCART_HTTP_HOST:-}"
FALLBACK_HOST="miigtools-ecommerce-production.up.railway.app"

if [ -n "$EXPLICIT_URL" ]; then
  STORE_URL="${EXPLICIT_URL%/}/"
else
  HTTP_HOST="${RAILWAY_HOST:-${CUSTOM_HOST:-localhost}}"

  # Block miigtools.com.br until the custom domain is intentionally enabled.
  if [ "$ALLOW_CUSTOM" != "1" ]; then
    case "${HTTP_HOST%%:*}" in
      miigtools.com.br|www.miigtools.com.br|*.miigtools.com.br)
        if [ -n "$RAILWAY_HOST" ]; then
          echo "[update-store-url] ignorando host custom ${HTTP_HOST}; usando RAILWAY_PUBLIC_DOMAIN=${RAILWAY_HOST}"
          HTTP_HOST="$RAILWAY_HOST"
        else
          echo "[update-store-url] miigtools.com.br ainda não liberado; fallback=${FALLBACK_HOST}"
          HTTP_HOST="$FALLBACK_HOST"
        fi
        ;;
    esac
  fi

  STORE_URL="${HTTP_SCHEME}://${HTTP_HOST}/"
fi

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
INSERT INTO \`${TABLE}\` (\`store_id\`, \`code\`, \`key\`, \`value\`, \`serialized\`) VALUES
  (0, 'config', 'config_url', '${STORE_URL}', 0),
  (0, 'config', 'config_secure', '${STORE_URL}', 0)
ON DUPLICATE KEY UPDATE \`value\` = VALUES(\`value\`);
SQL

echo "[update-store-url] Concluído."
