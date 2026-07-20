<?php
namespace Opencart\Catalog\Model\Tool;

/**
 * GA4 ecommerce helpers for Miigtools storefront.
 */
class Ga4 extends \Opencart\System\Engine\Model {
	/**
	 * Active storefront currency code.
	 */
	public function getCurrency(): string {
		if (!empty($this->session->data['currency'])) {
			return (string)$this->session->data['currency'];
		}

		return (string)$this->config->get('config_currency');
	}

	/**
	 * Numeric price in active currency (no formatting).
	 */
	public function formatAmount(float $amount): float {
		return (float)$this->currency->format($amount, $this->getCurrency(), 0.0, false);
	}

	/**
	 * Build a GA4 item from a catalog product row.
	 *
	 * @param array<string, mixed> $product
	 * @param array<string, mixed> $extra
	 *
	 * @return array<string, mixed>
	 */
	public function itemFromProduct(array $product, int $quantity = 1, array $extra = []): array {
		$unit = (float)(!empty($product['special']) ? $product['special'] : ($product['price'] ?? 0));

		if (isset($product['tax_class_id'])) {
			$unit = $this->tax->calculate($unit, (int)$product['tax_class_id'], $this->config->get('config_tax'));
		}

		$item = [
			'item_id'   => (string)(!empty($product['model']) ? $product['model'] : ($product['product_id'] ?? '')),
			'item_name' => (string)($product['name'] ?? ''),
			'price'     => $this->formatAmount($unit),
			'quantity'  => max(1, $quantity)
		];

		if (!empty($product['manufacturer'])) {
			$item['item_brand'] = (string)$product['manufacturer'];
		}

		return $extra + $item;
	}

	/**
	 * Build a GA4 item from a cart line.
	 *
	 * @param array<string, mixed> $product
	 *
	 * @return array<string, mixed>
	 */
	public function itemFromCart(array $product): array {
		$unit = $this->tax->calculate((float)$product['price'], (int)$product['tax_class_id'], $this->config->get('config_tax'));

		return [
			'item_id'   => (string)(!empty($product['model']) ? $product['model'] : $product['product_id']),
			'item_name' => (string)$product['name'],
			'price'     => $this->formatAmount($unit),
			'quantity'  => (int)$product['quantity']
		];
	}

	/**
	 * @param array<int, array<string, mixed>> $items
	 *
	 * @return array{event: string, params: array<string, mixed>}
	 */
	public function event(string $name, array $items, array $params = []): array {
		$params['currency'] = $this->getCurrency();
		$params['items'] = array_values($items);

		if (!isset($params['value']) && $items) {
			$value = 0.0;

			foreach ($items as $item) {
				$value += ((float)($item['price'] ?? 0)) * ((int)($item['quantity'] ?? 1));
			}

			$params['value'] = round($value, 2);
		}

		return [
			'event'  => $name,
			'params' => $params
		];
	}

	/**
	 * Current cart as GA4 items + value.
	 *
	 * @return array{items: array<int, array<string, mixed>>, value: float, currency: string}
	 */
	public function cartSnapshot(): array {
		$items = [];
		$value = 0.0;

		foreach ($this->cart->getProducts() as $product) {
			$item = $this->itemFromCart($product);
			$items[] = $item;
			$value += $item['price'] * $item['quantity'];
		}

		return [
			'items'    => $items,
			'value'    => round($value, 2),
			'currency' => $this->getCurrency()
		];
	}

	/**
	 * @param array<string, mixed> $order_info
	 *
	 * @return array{event: string, params: array<string, mixed>}|null
	 */
	public function eventPurchase(array $order_info): ?array {
		if (empty($order_info['order_id'])) {
			return null;
		}

		$currency = !empty($order_info['currency_code']) ? (string)$order_info['currency_code'] : $this->getCurrency();
		$items = [];

		foreach ($order_info['products'] ?? [] as $product) {
			$items[] = [
				'item_id'   => (string)(!empty($product['model']) ? $product['model'] : $product['product_id']),
				'item_name' => (string)$product['name'],
				'price'     => round((float)$product['price'] + (float)($product['tax'] ?? 0) / max(1, (int)$product['quantity']), 2),
				'quantity'  => (int)$product['quantity']
			];
		}

		$shipping = 0.0;
		$tax = 0.0;

		foreach ($order_info['totals'] ?? [] as $total) {
			if ($total['code'] === 'shipping') {
				$shipping = (float)$total['value'];
			}

			if ($total['code'] === 'tax') {
				$tax += (float)$total['value'];
			}
		}

		return [
			'event'  => 'purchase',
			'params' => [
				'transaction_id' => (string)$order_info['order_id'],
				'value'          => round((float)$order_info['total'], 2),
				'tax'            => round($tax, 2),
				'shipping'       => round($shipping, 2),
				'currency'       => $currency,
				'items'          => $items
			]
		];
	}

	/**
	 * Render JSON payload script for the storefront.
	 *
	 * @param array{event: string, params: array<string, mixed>}|null $event
	 */
	public function snippet(?array $event): string {
		if (!$event) {
			return '';
		}

		return $this->load->view('common/ga4_event', [
			'ga4'      => true,
			'ga4_json' => json_encode($event, JSON_UNESCAPED_UNICODE)
		]);
	}
}
