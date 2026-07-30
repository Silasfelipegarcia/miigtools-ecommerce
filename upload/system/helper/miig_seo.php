<?php
/**
 * SEO helpers: Open Graph + JSON-LD for MIIGTOOLS storefront.
 */

/**
 * Absolute storefront URL (trailing slash).
 */
function miig_seo_base_url(\Opencart\System\Engine\Registry $registry): string {
	$config = $registry->get('config');
	$base = (string)$config->get('config_url');

	if ($base === '') {
		$base = (string)$config->get('config_secure');
	}

	return rtrim($base, '/') . '/';
}

/**
 * Absolute URL for a catalog image path, or empty if missing.
 */
function miig_seo_image_url(\Opencart\System\Engine\Registry $registry, string $image): string {
	$image = html_entity_decode(trim($image), ENT_QUOTES, 'UTF-8');

	if ($image === '' || !is_file(DIR_IMAGE . $image)) {
		$logo = (string)$registry->get('config')->get('config_logo');

		if ($logo !== '' && is_file(DIR_IMAGE . $logo)) {
			return miig_seo_base_url($registry) . 'image/' . str_replace(' ', '%20', $logo);
		}

		return '';
	}

	return miig_seo_base_url($registry) . 'image/' . str_replace(' ', '%20', $image);
}

/**
 * Apply Open Graph + Twitter tags on the document.
 *
 * @param array<string, string> $og keys: title, description, url, image, type
 */
function miig_seo_set_open_graph(\Opencart\System\Library\Document $document, array $og): void {
	$document->setOpenGraph($og);
}

/**
 * Register a JSON-LD graph on the document.
 *
 * @param array<string, mixed> $schema
 */
function miig_seo_add_json_ld(\Opencart\System\Library\Document $document, array $schema): void {
	$document->addJsonLd($schema);
}

/**
 * Organization + WebSite schemas (home and global fallback).
 *
 * @return list<array<string, mixed>>
 */
function miig_seo_organization_schemas(\Opencart\System\Engine\Registry $registry): array {
	$config = $registry->get('config');
	$url = $registry->get('url');
	$base = miig_seo_base_url($registry);
	$name = (string)$config->get('config_name') ?: 'MIIGTOOLS';
	$search = $url->link('product/search', 'language=' . $config->get('config_language') . '&search={search_term_string}');

	$org = [
		'@context' => 'https://schema.org',
		'@type'    => 'Organization',
		'name'     => $name,
		'url'      => $base,
		'logo'     => miig_seo_image_url($registry, (string)$config->get('config_logo')),
	];

	$telephone = trim((string)$config->get('config_telephone'));

	if ($telephone !== '') {
		$org['telephone'] = $telephone;
	}

	$email = trim((string)$config->get('config_email'));

	if ($email !== '') {
		$org['email'] = $email;
	}

	$website = [
		'@context'        => 'https://schema.org',
		'@type'           => 'WebSite',
		'name'            => $name,
		'url'             => $base,
		'potentialAction' => [
			'@type'       => 'SearchAction',
			'target'      => [
				'@type'       => 'EntryPoint',
				'urlTemplate' => html_entity_decode($search, ENT_QUOTES, 'UTF-8'),
			],
			'query-input' => 'required name=search_term_string',
		],
	];

	return [$org, $website];
}

/**
 * BreadcrumbList from OpenCart breadcrumb arrays.
 *
 * @param list<array{text?:string,href?:string}> $breadcrumbs
 *
 * @return array<string, mixed>
 */
function miig_seo_breadcrumb_schema(array $breadcrumbs): array {
	$items = [];
	$position = 1;

	foreach ($breadcrumbs as $crumb) {
		$text = trim((string)($crumb['text'] ?? ''));
		$href = html_entity_decode((string)($crumb['href'] ?? ''), ENT_QUOTES, 'UTF-8');

		if ($text === '' || $href === '') {
			continue;
		}

		$items[] = [
			'@type'    => 'ListItem',
			'position' => $position++,
			'name'     => $text,
			'item'     => $href,
		];
	}

	return [
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	];
}

/**
 * Product + Offer JSON-LD. Omits Offer price when price <= 0.
 *
 * @param array<string, mixed> $product_info raw getProduct() row
 * @param list<array{text?:string,href?:string}> $breadcrumbs
 */
function miig_seo_product_schemas(
	\Opencart\System\Engine\Registry $registry,
	array $product_info,
	string $product_url,
	array $breadcrumbs,
	int $available
): array {
	$schemas = [];

	if ($breadcrumbs) {
		$schemas[] = miig_seo_breadcrumb_schema($breadcrumbs);
	}

	$name = (string)($product_info['name'] ?? '');
	$description = trim(strip_tags(html_entity_decode((string)($product_info['description'] ?? ''), ENT_QUOTES, 'UTF-8')));
	$meta_description = trim((string)($product_info['meta_description'] ?? ''));

	if ($description === '' && $meta_description !== '') {
		$description = $meta_description;
	}

	if (mb_strlen($description) > 5000) {
		$description = mb_substr($description, 0, 4997) . '...';
	}

	$product = [
		'@context'    => 'https://schema.org',
		'@type'       => 'Product',
		'name'        => $name,
		'url'         => html_entity_decode($product_url, ENT_QUOTES, 'UTF-8'),
		'sku'         => (string)($product_info['sku'] ?: $product_info['model'] ?? ''),
		'mpn'         => (string)($product_info['mpn'] ?: $product_info['model'] ?? ''),
		'description' => $description,
	];

	$image = miig_seo_image_url($registry, (string)($product_info['image'] ?? ''));

	if ($image !== '') {
		$product['image'] = [$image];
	}

	$brand = trim((string)($product_info['manufacturer'] ?? ''));

	if ($brand !== '') {
		$product['brand'] = [
			'@type' => 'Brand',
			'name'  => $brand,
		];
	}

	$price = (float)(!empty($product_info['special']) ? $product_info['special'] : ($product_info['price'] ?? 0));
	$currency = (string)$registry->get('session')->data['currency']
		?: (string)$registry->get('config')->get('config_currency')
		?: 'BRL';

	$availability = $available > 0
		? 'https://schema.org/InStock'
		: 'https://schema.org/OutOfStock';

	$offer = [
		'@type'         => 'Offer',
		'url'           => html_entity_decode($product_url, ENT_QUOTES, 'UTF-8'),
		'availability'  => $availability,
		'itemCondition' => 'https://schema.org/NewCondition',
		'seller'        => [
			'@type' => 'Organization',
			'name'  => (string)$registry->get('config')->get('config_name') ?: 'MIIGTOOLS',
		],
	];

	if ($price > 0) {
		$offer['price'] = number_format($price, 2, '.', '');
		$offer['priceCurrency'] = $currency;
	}

	$product['offers'] = $offer;
	$schemas[] = $product;

	return $schemas;
}

/**
 * FAQPage schema from Q&A pairs.
 *
 * @param list<array{question:string,answer:string}> $faqs
 *
 * @return array<string, mixed>|null
 */
function miig_seo_faq_schema(array $faqs): ?array {
	$entities = [];

	foreach ($faqs as $faq) {
		$q = trim((string)($faq['question'] ?? ''));
		$a = trim((string)($faq['answer'] ?? ''));

		if ($q === '' || $a === '') {
			continue;
		}

		$entities[] = [
			'@type'          => 'Question',
			'name'           => $q,
			'acceptedAnswer' => [
				'@type' => 'Answer',
				'text'  => $a,
			],
		];
	}

	if (!$entities) {
		return null;
	}

	return [
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => $entities,
	];
}

/**
 * Default technical FAQs for PDP (stable copy — safe for FAQPage schema).
 *
 * @return list<array{question:string,answer:string}>
 */
function miig_seo_default_product_faqs(): array {
	return [
		[
			'question' => 'O que significa a norma DIN na ficha?',
			'answer'   => 'Quando a ficha indica DIN, a ferramenta segue a geometria e tolerâncias da norma citada (por exemplo DIN 376 para machos). Confira a medida e o material da peça antes de comprar.',
		],
		[
			'question' => 'Como escolher HSS ou HSS com cobalto?',
			'answer'   => 'HSS (aço rápido) cobre a maioria das operações em aço. Linhas com ~10% Co resistem melhor a calor e materiais mais duros. Em dúvida, fale no WhatsApp com o modelo da ferramenta.',
		],
	];
}
