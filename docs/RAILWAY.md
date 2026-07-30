# Deploy MIIGTOOLS no Railway

## Resumo

Sim — você precisa de um **MySQL no Railway**. O serviço web (OpenCart) e o banco são **dois serviços** no mesmo projeto.

## Passo a passo

### 1. Criar projeto no Railway

1. Acesse [railway.app](https://railway.app) e crie um projeto.
2. Conecte o repositório GitHub `miigtools-ecommerce` (branch `main`).

### 2. Adicionar MySQL

1. No projeto, clique em **+ New** → **Database** → **MySQL**.
2. O Railway cria automaticamente as variáveis:
   - `MYSQLHOST`
   - `MYSQLUSER`
   - `MYSQLPASSWORD`
   - `MYSQLDATABASE`
   - `MYSQLPORT`

### 3. Configurar o serviço web (OpenCart)

1. O Railway detecta `railway.toml` e usa `Dockerfile.railway`.
2. No serviço web, abra **Variables** e adicione referências ao MySQL:
   - Clique em **Add Variable** → **Add Reference** → selecione o serviço MySQL.
   - Referencie todas as variáveis `MYSQL*`.
3. Adicione também:
   ```
   DB_PREFIX=ws_
   OPENCART_HTTP_SCHEME=https
   ```

O `entrypoint.sh` lê `MYSQL*` e gera `config.php` / `admin/config.php` na subida do container.

### 4. Exportar banco local e importar no Railway

No seu Mac:

```bash
chmod +x scripts/export-database.sh
./scripts/export-database.sh winner_steel database/winner_steel.sql
```

Faça login e importe com o script (usa `npx`, não precisa instalar globalmente):

```bash
npx @railway/cli login
npx @railway/cli link
./scripts/import-railway.sh
```

Ou importe manualmente (substitua `--service MySQL` se o serviço tiver outro nome):

```bash
npx @railway/cli run --service MySQL mysql -h "$MYSQLHOST" -u "$MYSQLUSER" -p"$MYSQLPASSWORD" -P "$MYSQLPORT" "$MYSQLDATABASE" < database/winner_steel.sql
npx @railway/cli run --service MySQL mysql ... < database/update-railway-url.sql
```

**Alternativa sem CLI:** no painel Railway → serviço MySQL → **Connect** → use TablePlus/DBeaver com a URL pública e importe `database/winner_steel.sql`, depois rode `database/update-railway-url.sql`.

### Erro: `Table 'railway.ws_store' doesn't exist`

Significa que o MySQL do Railway está **vazio** — o deploy subiu, mas o dump local ainda não foi importado. Siga o passo 4 acima.

### 5. Domínio e URLs da loja

1. No serviço web, gere um domínio em **Settings** → **Networking** → **Generate Domain** (ex.: `miigtools-ecommerce-production.up.railway.app`).
2. A cada deploy, o `write-config.php` / `update-store-url.sh` atualiza `config_url` no banco para o domínio **Railway**.
3. A loja também usa o **host da requisição** em runtime — se você abrir o Railway, CSS/JS vêm do Railway mesmo que alguém tenha salvo `miigtools.com.br` no admin.
4. **Não** configure `OPENCART_HTTP_HOST=www.miigtools.com.br` até o domínio custom estar no ar (DNS + Railway).
5. Quando `miigtools.com.br` estiver live:
   ```
   OPENCART_ALLOW_CUSTOM_DOMAIN=1
   OPENCART_STORE_URL=https://www.miigtools.com.br/
   ```
6. **Admin:** use `https://SEU-DOMINIO.up.railway.app/admin/` (com barra final).

### 6. Mercado Pago em produção

No admin → **Extensões** → **Pagamentos** → **Mercado Pago**:

- Ambiente: **Produção**
- Credenciais de produção do [Mercado Pago Developers](https://www.mercadopago.com.br/developers)
- Webhook: `https://SEU-DOMINIO.up.railway.app/index.php?route=extension/miigtools/payment/mercadopago.webhook`

## Variáveis de ambiente

| Variável | Origem |
|----------|--------|
| `MYSQLHOST`, `MYSQLUSER`, … | Plugin MySQL (referência) |
| `DB_PREFIX` | `ws_` (manual) |
| `RAILWAY_PUBLIC_DOMAIN` | Railway (automático) |
| `PORT` | Railway (automático) |
| `OPENCART_HTTP_SCHEME` | `https` (recomendado) |
| `OPENCART_ALLOW_CUSTOM_DOMAIN` | só `1` quando `miigtools.com.br` estiver no ar |
| `OPENCART_STORE_URL` | URL canônica opcional (ex. `https://www.miigtools.com.br/`) |

## Observações importantes

- **Storage** (`/storage/`): cache e sessões são efêmeros a cada redeploy. Para uploads persistentes, adicione um **Volume** no Railway montado em `/storage`.
- **Não commite** `upload/config.php` — já está no `.gitignore`; o container gera na runtime.
- O healthcheck usa `/health.php` (não depende do MySQL).
- **SEO:** após deploy, confira `/sitemap.xml` e o checklist em [docs/SEO.md](SEO.md) (Search Console, rich results, rituais GA4).

## Comandos úteis

```bash
railway logs
railway status
railway open
```
