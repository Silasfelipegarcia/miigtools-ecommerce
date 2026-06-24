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

1. Crie um projeto no [Railway](https://railway.app/) e conecte este repositório GitHub.
2. Adicione um serviço **MySQL** ao projeto.
3. No serviço web, configure as variáveis de ambiente (ou use as variáveis `MYSQL*` geradas pelo plugin MySQL).
4. O deploy usa `Dockerfile.railway` e gera `config.php` automaticamente na primeira execução.

Variáveis suportadas: veja `.env.example`.

## Estrutura

| Pasta | Descrição |
|-------|-----------|
| `upload/` | Código da loja (web root) |
| `storage/` | Cache, sessões, logs e vendor |
| `upload/extension/miigtools/` | Extensões customizadas MiigTools |
