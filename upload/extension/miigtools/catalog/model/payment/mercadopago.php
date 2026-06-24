<?php
namespace Opencart\Catalog\Model\Extension\Miigtools\Payment;
/**
 * Class Mercadopago
 */
class Mercadopago extends \Opencart\System\Engine\Model {
	public function getMethods(array $address = []): array {
		$this->load->language('extension/miigtools/payment/mercadopago');

		$status = (bool)$this->config->get('payment_mercadopago_status');

		if ($status && $this->config->get('payment_mercadopago_geo_zone_id')) {
			$this->load->model('localisation/geo_zone');

			$results = $this->model_localisation_geo_zone->getGeoZone(
				(int)$this->config->get('payment_mercadopago_geo_zone_id'),
				(int)($address['country_id'] ?? 0),
				(int)($address['zone_id'] ?? 0)
			);

			$status = (bool)$results;
		}

		if ($status && $this->session->data['currency'] !== 'BRL') {
			$status = false;
		}

		$method_data = [];

		if ($status) {
			$option_data['mercadopago'] = [
				'code' => 'mercadopago.mercadopago',
				'name' => $this->language->get('text_title')
			];

			$method_data = [
				'code'       => 'mercadopago',
				'name'       => $this->language->get('heading_title'),
				'option'     => $option_data,
				'sort_order' => (int)$this->config->get('payment_mercadopago_sort_order')
			];
		}

		return $method_data;
	}

	public function getPublicKey(): string {
		if ($this->config->get('payment_mercadopago_environment') === 'production') {
			return (string)$this->config->get('payment_mercadopago_public_key');
		}

		return (string)$this->config->get('payment_mercadopago_public_key_test');
	}

	public function getAccessToken(): string {
		if ($this->config->get('payment_mercadopago_environment') === 'production') {
			return (string)$this->config->get('payment_mercadopago_access_token');
		}

		return (string)$this->config->get('payment_mercadopago_access_token_test');
	}
}
