#!/bin/bash
# Exporta o banco local para importar no MySQL do Railway.
set -euo pipefail

DB_NAME="${1:-winner_steel}"
OUT="${2:-database/winner_steel.sql}"

mkdir -p "$(dirname "$OUT")"

mysqldump -u root \
  --single-transaction \
  --routines \
  --triggers \
  --set-gtid-purged=OFF \
  "$DB_NAME" > "$OUT"

echo "Exportado: $OUT ($(wc -c < "$OUT" | tr -d ' ') bytes)"
