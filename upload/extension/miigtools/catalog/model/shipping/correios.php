<?php
namespace Opencart\Catalog\Model\Extension\Miigtools\Shipping;
/**
 * Frete PAC/SEDEX aproximado Correios — origem Imirim, São Paulo/SP.
 * Tabela compartilhada com a cotação do PDP/carrinho.
 */
class Correios extends \Opencart\System\Engine\Model {
	public function getQuote(array $address): array {
		$this->load->language('extension/miigtools/shipping/correios');
		$this->load->helper('miig_correios');

		if (!$this->config->get('shipping_correios_status')) {
			return [];
		}

		$postcode = preg_replace('/\D+/', '', (string)($address['postcode'] ?? ''));
		$uf = strtoupper((string)($address['zone_code'] ?? ''));

		if (strlen($postcode) !== 8 && $uf === '') {
			return [];
		}

		$weight = miig_correios_normalize_weight((float)$this->cart->getWeight());
		$band = miig_correios_distance_band($postcode, $uf);
		$rows = miig_correios_quotes($band, $weight);

		$quote_data = [];

		foreach ($rows as $row) {
			$code = $row['code'];
			$title = $code === 'sedex' ? $this->language->get('text_sedex') : $this->language->get('text_pac');

			$quote_data[$code] = [
				'code'         => 'correios.' . $code,
				'name'         => $title,
				'cost'         => $row['cost'],
				'tax_class_id' => (int)$this->config->get('shipping_correios_tax_class_id'),
				'text'         => $this->currency->format(
					$this->tax->calculate($row['cost'], (int)$this->config->get('shipping_correios_tax_class_id'), $this->config->get('config_tax')),
					$this->session->data['currency']
				)
			];
		}

		return [
			'code'       => 'correios',
			'name'       => $this->language->get('heading_title'),
			'quote'      => $quote_data,
			'sort_order' => (int)$this->config->get('shipping_correios_sort_order'),
			'error'      => false
		];
	}
}
