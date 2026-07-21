<?php
namespace Opencart\Catalog\Controller\Product;
/**
 * Class Thumb
 *
 * Can be loaded using $this->load->controller('product/thumb', $product_data);
 *
 * @example
 *
 * $product_data = [
 *     'description' => '',
 *     'thumb'       => '',
 *     'price'       => 1.00,
 *     'special'     => 0.00,
 *     'tax'         => 0.00,
 *     'minimum'     => 1,
 *     'href'        => ''
 * ];
 *
 * @package Opencart\Catalog\Controller\Product
 */
class Thumb extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @param array<string, mixed> $data array of data
	 *
	 * @return string
	 */
	public function index(array $data): string {
		$this->load->language('product/thumb');

		$data['cart'] = $this->url->link('common/cart.info', 'language=' . $this->config->get('config_language'));

		$data['cart_add'] = $this->url->link('checkout/cart.add', 'language=' . $this->config->get('config_language'));
		$data['wishlist_add'] = $this->url->link('account/wishlist.add', 'language=' . $this->config->get('config_language'));
		$data['compare_add'] = $this->url->link('product/compare.add', 'language=' . $this->config->get('config_language'));

		$data['review_status'] = (int)$this->config->get('config_review_status');

		$qty = isset($data['quantity']) ? (int)$data['quantity'] : null;
		$subtract = !empty($data['subtract']);
		$product_info = null;

		if ($qty === null && !empty($data['product_id'])) {
			$this->load->model('catalog/product');
			$product_info = $this->model_catalog_product->getProduct((int)$data['product_id']);

			if ($product_info) {
				$qty = (int)$product_info['quantity'];
				$subtract = !empty($product_info['subtract']);
			}
		}

		if ($qty !== null && $subtract) {
			if ($qty > 0) {
				$data['stock_badge'] = $this->language->get('text_stock_in');
				$data['stock_badge_class'] = 'in';
				$data['stock_badge_qty'] = sprintf($this->language->get('text_stock_qty'), $qty);
			} else {
				$data['stock_badge'] = $this->language->get('text_stock_out');
				$data['stock_badge_class'] = 'out';
				$data['stock_badge_qty'] = '';
			}
		} else {
			$data['stock_badge'] = '';
			$data['stock_badge_class'] = '';
			$data['stock_badge_qty'] = '';
		}

		$data['ga_item'] = '';

		if (!empty($data['product_id'])) {
			$this->load->model('catalog/product');
			$this->load->model('tool/ga4');

			$product_info = $product_info ?? $this->model_catalog_product->getProduct((int)$data['product_id']);

			if ($product_info) {
				$data['ga_item'] = htmlspecialchars(json_encode($this->model_tool_ga4->itemFromProduct($product_info, max(1, (int)($data['minimum'] ?? 1))), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
			}
		}

		return $this->load->view('product/thumb', $data);
	}
}
