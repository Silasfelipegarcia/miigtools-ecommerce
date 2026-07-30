<?php
namespace Opencart\Catalog\Controller\Information;
/**
 * Class SitemapXml
 *
 * XML sitemap for Google (products with price > 0, categories, informations, manufacturers).
 *
 * @package Opencart\Catalog\Controller\Information
 */
class SitemapXml extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$lang = $this->config->get('config_language');
		$urls = [];

		$urls[] = [
			'loc'        => $this->url->link('common/home', 'language=' . $lang),
			'changefreq' => 'daily',
			'priority'   => '1.0',
		];

		$this->load->model('catalog/category');

		foreach ($this->collectCategoryPaths(0, '') as $path) {
			$urls[] = [
				'loc'        => $this->url->link('product/category', 'language=' . $lang . '&path=' . $path),
				'changefreq' => 'weekly',
				'priority'   => '0.8',
			];
		}

		$this->load->model('catalog/product');

		$filter = [
			'start' => 0,
			'limit' => 50000,
		];

		$products = $this->model_catalog_product->getProducts($filter);

		foreach ($products as $product) {
			if ((float)($product['price'] ?? 0) <= 0) {
				continue;
			}

			$urls[] = [
				'loc'        => $this->url->link('product/product', 'language=' . $lang . '&product_id=' . (int)$product['product_id']),
				'changefreq' => 'weekly',
				'priority'   => '0.7',
				'lastmod'    => !empty($product['date_modified']) ? date('Y-m-d', strtotime($product['date_modified'])) : '',
			];
		}

		$this->load->model('catalog/information');

		foreach ($this->model_catalog_information->getInformations() as $information) {
			$urls[] = [
				'loc'        => $this->url->link('information/information', 'language=' . $lang . '&information_id=' . (int)$information['information_id']),
				'changefreq' => 'monthly',
				'priority'   => '0.6',
			];
		}

		$this->load->model('catalog/manufacturer');

		foreach ($this->model_catalog_manufacturer->getManufacturers() as $manufacturer) {
			$urls[] = [
				'loc'        => $this->url->link('product/manufacturer.info', 'language=' . $lang . '&manufacturer_id=' . (int)$manufacturer['manufacturer_id']),
				'changefreq' => 'monthly',
				'priority'   => '0.5',
			];
		}

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		foreach ($urls as $row) {
			$loc = htmlspecialchars(html_entity_decode($row['loc'], ENT_QUOTES, 'UTF-8'), ENT_XML1 | ENT_QUOTES, 'UTF-8');
			$xml .= "  <url>\n";
			$xml .= '    <loc>' . $loc . "</loc>\n";

			if (!empty($row['lastmod'])) {
				$xml .= '    <lastmod>' . htmlspecialchars($row['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</lastmod>\n";
			}

			if (!empty($row['changefreq'])) {
				$xml .= '    <changefreq>' . $row['changefreq'] . "</changefreq>\n";
			}

			if (!empty($row['priority'])) {
				$xml .= '    <priority>' . $row['priority'] . "</priority>\n";
			}

			$xml .= "  </url>\n";
		}

		$xml .= '</urlset>';

		$this->response->addHeader('Content-Type: application/xml; charset=utf-8');
		$this->response->setOutput($xml);
	}

	/**
	 * @return list<string>
	 */
	private function collectCategoryPaths(int $parent_id, string $prefix): array {
		$paths = [];
		$categories = $this->model_catalog_category->getCategories($parent_id);

		foreach ($categories as $category) {
			$path = $prefix === '' ? (string)(int)$category['category_id'] : $prefix . '_' . (int)$category['category_id'];
			$paths[] = $path;
			$paths = array_merge($paths, $this->collectCategoryPaths((int)$category['category_id'], $path));
		}

		return $paths;
	}
}
