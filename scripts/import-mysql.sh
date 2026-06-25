#!/bin/bash
# Importa winner_steel.sql no MySQL do Railway via cliente mysql local.
# Uso: ./scripts/import-mysql.sh
set -euo pipefail

SQL_FILE="$(cd "$(dirname "$0")/.." && pwd)/database/winner_steel.sql"
RAILWAY_URL="https://miigtools-ecommerce-production.up.railway.app"

echo "=== Importar banco OpenCart no Railway ==="
echo ""
echo "Pegue no Railway → MySQL → Connect:"
echo "  Host, Port, User, Password, Database (railway)"
echo ""

read -r -p "Host (ex: switchback.proxy.rlwy.net): " MYSQL_HOST
read -r -p "Port (ex: 11332): " MYSQL_PORT
read -r -p "User (ex: root): " MYSQL_USER
read -r -s -p "Password: " MYSQL_PASS
echo ""
read -r -p "Database [railway]: " MYSQL_DB
MYSQL_DB="${MYSQL_DB:-railway}"

if [ ! -f "$SQL_FILE" ]; then
  echo "Arquivo não encontrado: $SQL_FILE"
  echo "Rode: ./scripts/export-database.sh"
  exit 1
fi

echo ""
echo "Importando $(basename "$SQL_FILE") ... (pode levar 1-2 min)"
mysql -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" -p"$MYSQL_PASS" "$MYSQL_DB" < "$SQL_FILE"

echo "Atualizando URLs..."
mysql -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" -p"$MYSQL_PASS" "$MYSQL_DB" -e "
UPDATE ws_setting SET value = '${RAILWAY_URL}/' WHERE \`key\` = 'config_url' AND store_id = 0;
UPDATE ws_setting SET value = '${RAILWAY_URL}/' WHERE \`key\` = 'config_ssl' AND store_id = 0;
UPDATE ws_store SET url = '${RAILWAY_URL}/' WHERE store_id = 0;
SELECT store_id, name, url FROM ws_store;
"

echo ""
echo "Pronto! Acesse: ${RAILWAY_URL}/"
