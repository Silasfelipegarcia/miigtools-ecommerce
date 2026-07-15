# miigtools-ecommerce

E-commerce OpenCart 4 com extensões MiigTools (Mercado Pago, customizações de loja).

## Stack

- OpenCart 4.1
- PHP 8.2 + Apache
- MySQL
- Extensão `miigtools` em `upload/extension/miigtools/`

## Desenvolvimento local

```bash
# Colima precisa estar rodando (Docker)
colima start

docker compose up -d --build
```

| Serviço | URL |
|---------|-----|
| Loja | http://localhost:8888/ |
| Admin | http://localhost:8888/admin/ (`admin` / `admin`) |
| Adminer (DB) | http://localhost:8889/ (server `mysql`, user `root`, pass `opencart`) |

Na primeira subida o OpenCart é instalado automaticamente (prefixo `ws_`). Para reinstalar do zero: apague `upload/install.lock` e o volume MySQL (`docker compose down -v`).

Se tiver o dump de produção (`database/winner_steel.sql`), importe no Adminer para trazer produtos e configurações reais.

## Deploy no Railway

Guia completo: **[docs/RAILWAY.md](docs/RAILWAY.md)**

Resumo:

1. Crie projeto no [Railway](https://railway.app/) e conecte este repositório.
2. Adicione serviço **MySQL** (Database → MySQL).
3. No serviço web, referencie as variáveis `MYSQL*` do banco.
4. Defina `DB_PREFIX=ws_` e `OPENCART_HTTP_SCHEME=https`.
5. Exporte o banco local: `./scripts/export-database.sh` e importe no MySQL do Railway.
6. Gere domínio público no serviço web.

O deploy usa `Dockerfile.railway` + `scripts/entrypoint.sh` (Apache na porta do Railway, `config.php` gerado automaticamente).

Variáveis: veja `.env.example`.

## Estrutura

| Pasta | Descrição |
|-------|-----------|
| `upload/` | Código da loja (web root) |
| `storage/` | Cache, sessões, logs e vendor |
| `upload/extension/miigtools/` | Extensões customizadas MiigTools |
