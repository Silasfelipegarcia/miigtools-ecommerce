<?php
namespace Opencart\Catalog\Controller\Product;
/**
 * Class Search
 *
 * @package Opencart\Catalog\Controller\Product
 */
class Search extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('product/search');

		if (isset($this->request->get['search'])) {
			$filter_search = $this->request->get['search'];
		} else {
			$filter_search = '';
		}

		if (isset($this->request->get['description'])) {
			$filter_description = $this->request->get['description'];
		} else {
			$filter_description = '';
		}

		if (isset($this->request->get['tag'])) {
			$filter_tag = $this->request->get['tag'];
		} else {
			$filter_tag = '';
		}

		if (isset($this->request->get['category_id'])) {
			$filter_category_id = (int)$this->request->get['category_id'];
		} else {
			$filter_category_id = 0;
		}

		if (isset($this->request->get['sub_category'])) {
			$filter_sub_category = $this->request->get['sub_category'];
		} else {
			$filter_sub_category = 0;
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'p.sort_order';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		if (isset($this->request->get['limit']) && (int)$this->request->get['limit']) {
			$limit = (int)$this->request->get['limit'];
		} else {
			$limit = $this->config->get('config_pagination');
		}

		if (isset($this->request->get['search'])) {
			$this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->request->get['search']);
		} elseif (isset($this->request->get['tag'])) {
			$this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('heading_tag') . $this->request->get['tag']);
		} else {
			$this->document->setTitle($this->language->get('heading_title'));
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];

		$url = '';

		if (isset($this->request->get['search'])) {
			$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['description'])) {
			$url .= '&description=' . $this->request->get['description'];
		}

		if (isset($this->request->get['tag'])) {
			$url .= '&tag=' . urlencode(html_entity_decode($this->request->get['tag'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['category_id'])) {
			$url .= '&category_id=' . $this->request->get['category_id'];
		}

		if (isset($this->request->get['sub_category'])) {
			$url .= '&sub_category=' . $this->request->get['sub_category'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		if (isset($this->request->get['limit'])) {
			$url .= '&limit=' . $this->request->get['limit'];
		}

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('product/search', 'language=' . $this->config->get('config_language') . $url)
		];

		if (isset($this->request->get['search'])) {
			$data['heading_title'] = $this->language->get('heading_title') . ' - ' . $this->request->get['search'];
		} else {
			$data['heading_title'] = $this->language->get('heading_title');
		}

		$data['text_compare'] = sprintf($this->language->get('text_compare'), isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0);

		$data['compare'] = $this->url->link('product/compare', 'language=' . $this->config->get('config_language'));

		// 3 Level Category Search
		$data['categories'] = [];

		$this->load->model('catalog/category');

		$categories_1 = $this->model_catalog_category->getCategories(0);

		foreach ($categories_1 as $category_1) {
			$level_2_data = [];

			$categories_2 = $this->model_catalog_category->getCategories($category_1['category_id']);

			foreach ($categories_2 as $category_2) {
				$level_3_data = $this->model_catalog_category->getCategories($category_2['category_id']);

				$level_2_data[] = [
					'category_id' => $category_2['category_id'],
					'name'        => $category_2['name'],
					'children'    => $level_3_data
				];
			}

			$data['categories'][] = ['children' => $level_2_data] + $category_1;
		}

		$data['products'] = [];

		$search_original = $filter_search;
		$this->load->helper('miig_search');
		$filter_search_expanded = miig_expand_search_query($filter_search);

		if ($filter_search || $filter_tag) {
			$filter_data = [
				'filter_search'       => $filter_search_expanded !== '' ? $filter_search_expanded : $filter_search,
				'filter_description'  => $filter_description,
				'filter_tag'          => $filter_tag ? $filter_tag : ($filter_search_expanded !== '' ? $filter_search_expanded : $filter_search),
				'filter_category_id'  => $filter_category_id,
				'filter_sub_category' => $filter_sub_category,
				'sort'                => $sort,
				'order'               => $order,
				'start'               => ($page - 1) * $limit,
				'limit'               => $limit
			];

			// Product
			$this->load->model('catalog/product');

			// Image
			$this->load->model('tool/image');

			$results = $this->model_catalog_product->getProducts($filter_data);

			// If synonym expansion found nothing, retry with the original query
			if (!$results && $filter_search_expanded !== '' && $filter_search_expanded !== $search_original) {
				$filter_data['filter_search'] = $search_original;
				$filter_data['filter_tag'] = $filter_tag ? $filter_tag : $search_original;
				$results = $this->model_catalog_product->getProducts($filter_data);
			}

			foreach ($results as $result) {
				$description = trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')));

				if (oc_strlen($description) > $this->config->get('config_product_description_length')) {
					$description = oc_substr($description, 0, $this->config->get('config_product_description_length')) . '..';
				}

				if ($result['image'] && is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8'))) {
					$image = $result['image'];
				} else {
					$image = 'placeholder.png';
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if ((float)$result['special']) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$special = false;
				}

				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
				} else {
					$tax = false;
				}

				$product_data = [
					'thumb'       => $this->model_tool_image->resize($image, $this->config->get('config_image_product_width'), $this->config->get('config_image_product_height')),
					'description' => $description,
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'href'        => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . $result['product_id'] . $url)
				] + $result;

				$data['products'][] = $this->load->controller('product/thumb', $product_data);
			}

			$url = '';

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . urlencode(html_entity_decode($this->request->get['tag'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['sorts'] = [];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_default'),
				'value' => 'p.sort_order-ASC',
				'href'  => $this->url->link('product/search', 'language=' . $this->config->get('config_language') . '&sort=p.sort_order&order=ASC' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_name_asc'),
				'value' => 'pd.name-ASC',
				'href'  => $this->url->link('product/search', 'language=' . $this->config->get('config_language') . '&sort=pd.name&order=ASC' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_name_desc'),
				'value' => 'pd.name-DESC',
				'href'  => $this->url->link('product/search', 'language=' . $this->config->get('config_language') . '&sort=pd.name&order=DESC' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_price_asc'),
				'value' => 'p.price-ASC',
				'href'  => $this->url->link('product/search', 'language=' . $this->config->get('config_language') . '&sort=p.price&order=ASC' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_price_desc'),
				'value' => 'p.price-DESC',
				'href'  => $this->url->link('product/search', 'language=' . $this->config->get('config_language') . '&sort=p.price&order=DESC' . $url)
			];

			if ($this->config->get('config_review_status')) {
				$data['sorts'][] = [
					'text'  => $this->language->get('text_rating_desc'),
					'value' => 'rating-DESC',
					'href'  => $this->url->link('product/search', 'language=' . $this->config->get('config_language') . '&sort=rating&order=DESC' . $url)
				];

				$data['sorts'][] = [
					'text'  => $this->language->get('text_rating_asc'),
					'value' => 'rating-ASC',
					'href'  => $this->url->link('product/search', 'language=' . $this->config->get('config_language') . '&sort=rating&order=ASC' . $url)
				];
			}

			$data['sorts'][] = [
				'text'  => $this->language->get('text_model_asc'),
				'value' => 'p.model-ASC',
				'href'  => $this->url->link('product/search', 'language=' . $this->config->get('config_language') . '&sort=p.model&order=ASC' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_model_desc'),
				'value' => 'p.model-DESC',
				'href'  => $this->url->link('product/search', 'language=' . $this->config->get('config_language') . '&sort=p.model&order=DESC' . $url)
			];

			$url = '';

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . urlencode(html_entity_decode($this->request->get['tag'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			$data['limits'] = [];

			$limits = array_unique([$this->config->get('config_pagination'), 25, 50, 75, 100]);

			sort($limits);

			foreach ($limits as $value) {
				$data['limits'][] = [
					'text'  => $value,
					'value' => $value,
					'href'  => $this->url->link('product/search', 'language=' . $this->config->get('config_language') . $url . '&limit=' . $value)
				];
			}

			$url = '';

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . urlencode(html_entity_decode($this->request->get['tag'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$product_total = $this->model_catalog_product->getTotalProducts($filter_data);

			$data['pagination'] = $this->load->controller('common/pagination', [
				'total' => $product_total,
				'page'  => $page,
				'limit' => $limit,
				'url'   => $this->url->link('product/search', 'language=' . $this->config->get('config_language') . $url . '&page={page}')
			]);

			$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

			if (isset($this->request->get['search']) && $this->config->get('config_customer_search')) {
				$this->load->model('account/search');

				if ($this->customer->isLogged()) {
					$customer_id = $this->customer->getId();
				} else {
					$customer_id = 0;
				}

				$search_data = [
					'keyword'      => $filter_tag ? $filter_tag : $filter_search,
					'description'  => $filter_description,
					'category_id'  => $filter_category_id,
					'sub_category' => $filter_sub_category,
					'products'     => $product_total,
					'customer_id'  => $customer_id,
					'ip'           => oc_get_ip()
				];

				$this->model_account_search->addSearch($search_data);
			}
		}

		$data['search'] = $filter_search;
		$data['description'] = $filter_description;
		$data['category_id'] = $filter_category_id;
		$data['sub_category'] = $filter_sub_category;

		$data['sort'] = $sort;
		$data['order'] = $order;
		$data['limit'] = $limit;

		$data['language'] = $this->config->get('config_language');

		$data['text_zero_help'] = $this->language->get('text_zero_help');
		$data['text_zero_whatsapp'] = $this->language->get('text_zero_whatsapp');
		$data['text_zero_categories'] = $this->language->get('text_zero_categories');
		$wa_zero = 'Olá! Busquei no site da MIIGTOOLS por "' . $filter_search . '" e não encontrei. Podem me ajudar a achar a ferramenta certa?';
		$data['whatsapp_zero_url'] = 'https://wa.me/551122360122?text=' . rawurlencode($wa_zero);
		$data['zero_category_links'] = [
			['name' => 'Bits', 'href' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=59_60')],
			['name' => 'Machos', 'href' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=59_63')],
			['name' => 'Pontas Rotativas', 'href' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=59_68')],
			['name' => 'Porta Recartilhas', 'href' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=59_67')],
		];

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$data['currency'] = $this->session->data['currency'];

		$this->load->model('tool/ga4');

		if (!empty($data['search'])) {
			$data['ga4'] = [
				'event'  => 'search',
				'params' => [
					'search_term' => (string)$data['search']
				]
			];
		} else {
			$data['ga4'] = null;
		}

		$data['ga4_event'] = $this->model_tool_ga4->snippet($data['ga4']);

		$this->response->setOutput($this->load->view('product/search', $data));
	}

	/**
	 * Autocomplete for header search (SKU / medida / nome).
	 */
	public function autocomplete(): void {
		$this->load->helper('miig_search');
		$this->load->model('catalog/product');

		$json = [];
		$search = trim((string)($this->request->get['search'] ?? ''));

		if (oc_strlen($search) >= 2) {
			$expanded = miig_expand_search_query($search);

			$filter_data = [
				'filter_search' => $expanded !== '' ? $expanded : $search,
				'filter_tag'    => $expanded !== '' ? $expanded : $search,
				'sort'          => 'pd.name',
				'order'         => 'ASC',
				'start'         => 0,
				'limit'         => 8
			];

			$results = $this->model_catalog_product->getProducts($filter_data);

			if (!$results && $expanded !== '' && $expanded !== $search) {
				$filter_data['filter_search'] = $search;
				$filter_data['filter_tag'] = $search;
				$results = $this->model_catalog_product->getProducts($filter_data);
			}

			$lang_id = (int)$this->config->get('config_language_id');

			foreach ($results as $result) {
				if ((float)$result['price'] <= 0) {
					continue;
				}

				$medida = '';
				$attr = $this->db->query(
					"SELECT pa.`text` FROM `" . DB_PREFIX . "product_attribute` pa
					 JOIN `" . DB_PREFIX . "attribute_description` ad ON ad.`attribute_id` = pa.`attribute_id` AND ad.`language_id` = '" . $lang_id . "'
					 WHERE pa.`product_id` = '" . (int)$result['product_id'] . "' AND pa.`language_id` = '" . $lang_id . "'
					   AND ad.`name` IN ('Medida','Size','Equivalente','Cross-ref')
					 ORDER BY FIELD(ad.`name`,'Medida','Size','Equivalente','Cross-ref')
					 LIMIT 1"
				);

				if ($attr->num_rows) {
					$medida = $attr->row['text'];
				}

				$price = $this->currency->format(
					$this->tax->calculate((float)$result['price'], $result['tax_class_id'], $this->config->get('config_tax')),
					$this->session->data['currency']
				);

				$parts = [$result['name']];

				if ($result['model']) {
					$parts[] = $result['model'];
				}

				if ($medida) {
					$parts[] = $medida;
				}

				$parts[] = $price;

				if ((int)$result['quantity'] > 0) {
					$parts[] = (int)$result['quantity'] . ' un.';
				}

				$json[] = [
					'label' => implode(' · ', $parts),
					'href'  => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . (int)$result['product_id']),
					'name'  => $result['name']
				];
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
