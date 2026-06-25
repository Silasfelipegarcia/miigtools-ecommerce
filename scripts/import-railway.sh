#!/bin/bash
# Importa winner_steel.sql no MySQL do Railway.
# Requer Railway CLI: npm i -g @railway/cli && railway login && railway link
set -euo pipefail

SQL_FILE="${1:-database/winner_steel.sql}"
RAILWAY_URL="${2:-https://miigtools-ecommerce-production.up.railway.app}"

if [ ! -f "$SQL_FILE" ]; then
  echo "Arquivo não encontrado: $SQL_FILE"
  echo "Rode antes: ./scripts/export-database.sh"
  exit 1
fi

RAILWAY="npx @railway/cli"

if ! $RAILWAY whoami >/dev/null 2>&1; then
  echo "Faça login no Railway:"
  echo "  npx @railway/cli login"
  echo "  npx @railway/cli link"
  exit 1
fi

echo "Importando $SQL_FILE no MySQL do Railway..."
$RAILWAY run --service MySQL mysql -h "\$MYSQLHOST" -u "\$MYSQLUSER" -p"\$MYSQLPASSWORD" -P "\$MYSQLPORT" "\$MYSQLDATABASE" < "$SQL_FILE"

echo "Atualizando URL da loja para $RAILWAY_URL ..."
$RAILWAY run --service MySQL mysql -h "\$MYSQLHOST" -u "\$MYSQLUSER" -p"\$MYSQLPASSWORD" -P "\$MYSQLPORT" "\$MYSQLDATABASE" -e "
UPDATE ws_setting SET value = '${RAILWAY_URL}/' WHERE \`key\` = 'config_url' AND store_id = 0;
UPDATE ws_setting SET value = '${RAILWAY_URL}/' WHERE \`key\` = 'config_ssl' AND store_id = 0;
UPDATE ws_setting SET value = '1', serialized = 0 WHERE \`key\` = 'config_customer_group_id' AND store_id = 0;
UPDATE ws_setting SET value = '[\"1\"]', serialized = 1 WHERE \`key\` = 'config_customer_group_display' AND store_id = 0;
INSERT IGNORE INTO ws_customer_group_description (customer_group_id, language_id, name, description) VALUES
  (1, 2, 'Padrão', 'Grupo de clientes padrão'),
  (2, 2, 'Varejo', 'Clientes de varejo'),
  (3, 2, 'Atacado', 'Clientes atacado');
UPDATE ws_store SET url = '${RAILWAY_URL}/' WHERE store_id = 0;
"

echo "Pronto. Acesse: ${RAILWAY_URL}/"
