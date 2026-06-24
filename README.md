# miigtools-ecommerce

E-commerce OpenCart 4 com extensões MiigTools (Mercado Pago, customizações de loja).

## Stack

- OpenCart 4.1
- PHP 8.2 + Apache
- MySQL
- Extensão `miigtools` em `upload/extension/miigtools/`

## Desenvolvimento local

```bash
docker-compose up -d
```

A loja fica em `http://localhost/` e o Adminer em `http://localhost:8080/`.

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
