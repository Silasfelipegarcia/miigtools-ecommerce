# SEO — MIIGTOOLS

Fundação técnica e rituais de medição para crescimento orgânico (plano SEO S1–S3).

## Endpoints

| URL | Função |
|-----|--------|
| `/sitemap.xml` | Sitemap XML (home, categorias, produtos com preço &gt; 0, informations, fabricantes) |
| `/robots.txt` | Bloqueia sort/filter/page; declara `Sitemap:` (domínio custom + Railway) |
| Landings | `/machos-para-aco`, `/bits-para-torno`, `/pontas-rotativas-cm`, `/ferramentas-din`, `/alargadores-h7`, `/porta-ferramentas`, `/faq-tecnico` |

PDPs e listagens emitem **JSON-LD** (Product/Offer, BreadcrumbList, Organization/WebSite) e **Open Graph / Twitter**.

## Google Search Console (operação)

1. Abrir [Google Search Console](https://search.google.com/search-console).
2. Adicionar propriedade URL do host ativo:
   - Produção atual: `https://miigtools-ecommerce-production.up.railway.app`
   - Quando o domínio estiver no ar: `https://www.miigtools.com.br` (e configurar `OPENCART_ALLOW_CUSTOM_DOMAIN=1`).
3. Verificar propriedade (DNS ou arquivo HTML / meta tag).
4. **Sitemaps** → enviar `sitemap.xml` (URL absoluta completa).
5. Validar rich results:
   - [Rich Results Test](https://search.google.com/test/rich-results) em 3 PDPs e 1 landing.
   - Conferir Product + BreadcrumbList (+ FAQPage em `/faq-tecnico` e PDP).

## Checklist pós-deploy

- [ ] `GET /sitemap.xml` retorna `200` e `Content-Type: application/xml`
- [ ] View-source na home: `og:title`, `application/ld+json` Organization/WebSite
- [ ] View-source em PDP: Product + Offer (sem `price` se preço ≤ 0) + BreadcrumbList + FAQPage
- [ ] Share no WhatsApp mostra título/imagem
- [ ] Após boot/redeploy, bootstrap rodou (`bootstrap-seo:` nos logs) — landings densas, meta preenchida, EQUIV nos top 50

## Ritual quinzenal (GA4 + Search Console)

1. **GA4** — Relatório Aquisição / Páginas de destino: landings de aplicação no top 20?
2. **Search Console** — Consultas com impressões altas e CTR baixo → ajustar `meta_title` / `meta_description` do SKU ou landing.
3. **Cobertura** — Páginas excluídas / erro de sitemap; produtos preço 0 não devem aparecer no XML.
4. Anotar 5 queries novas de cauda longa (norma/medida) para conteúdo ou cross-ref EQUIV.

## Bootstrap

Em cada start (Railway/`write-config.php`):

- `upload/system/miigtools/bootstrap_seo.php` → `bootstrap_seo_growth()`
- Preenche landings, FAQ, metas vazias, códigos `EQUIV`, keyword `sitemap.xml`

## Hipóteses (validação)

1. Tráfego B2B chega por norma/aplicação mais que por marca.
2. Landings densas + Product schema sobem CTR vs. só PLP.
3. FAQ na PDP reduz bounce e ajuda `begin_checkout` (já no funil GA4).
