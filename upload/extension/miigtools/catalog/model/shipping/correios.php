<?php
namespace Opencart\Catalog\Model\Extension\Miigtools\Shipping;
/**
 * Frete PAC/SEDEX aproximado Correios — origem Imirim, São Paulo/SP.
 */
class Correios extends \Opencart\System\Engine\Model {
	private const ORIGIN_POSTCODE = '02465000';

	public function getQuote(array $address): array {
		$this->load->language('extension/miigtools/shipping/correios');

		if (!$this->config->get('shipping_correios_status')) {
			return [];
		}

		$postcode = preg_replace('/\D+/', '', (string)($address['postcode'] ?? ''));
		$uf = strtoupper((string)($address['zone_code'] ?? ''));

		if (strlen($postcode) !== 8 && $uf === '') {
			return [];
		}

		$weight = (float)$this->cart->getWeight();

		if ($weight > 50) {
			$weight = $weight / 1000;
		}

		if ($weight <= 0) {
			$weight = 0.5;
		}

		$weight = max(0.3, min(30.0, $weight));
		$band = $this->distanceBand($postcode, $uf);
		$rows = $this->quoteTable($band, $weight);

		$quote_data = [];

		foreach ($rows as $code => $row) {
			$quote_data[$code] = [
				'code'         => 'correios.' . $code,
				'name'         => $row['title'],
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

	private function distanceBand(string $postcode, string $uf): string {
		$uf = strtoupper($uf);
		$prefix = strlen($postcode) >= 2 ? (int)substr($postcode, 0, 2) : 0;

		if ($uf === 'SP' && $prefix >= 1 && $prefix <= 5) {
			return 'local';
		}

		if ($uf === 'SP') {
			return 'near';
		}

		if (in_array($uf, ['RJ', 'MG', 'PR', 'ES'], true)) {
			return 'near';
		}

		if (in_array($uf, ['SC', 'RS', 'GO', 'DF', 'MS', 'MT', 'BA'], true)) {
			return 'mid';
		}

		return 'far';
	}

	/**
	 * @return array<string, array{title: string, cost: float}>
	 */
	private function quoteTable(string $band, float $weight_kg): array {
		$table = [
			'local' => [
				'pac'   => ['base' => 22.90, 'per_kg' => 4.50],
				'sedex' => ['base' => 32.50, 'per_kg' => 6.80],
			],
			'near'  => [
				'pac'   => ['base' => 34.90, 'per_kg' => 7.20],
				'sedex' => ['base' => 48.90, 'per_kg' => 9.50],
			],
			'mid'   => [
				'pac'   => ['base' => 48.50, 'per_kg' => 9.80],
				'sedex' => ['base' => 68.90, 'per_kg' => 12.50],
			],
			'far'   => [
				'pac'   => ['base' => 62.90, 'per_kg' => 12.00],
				'sedex' => ['base' => 89.90, 'per_kg' => 15.50],
			],
		];

		$row = $table[$band] ?? $table['mid'];
		$extra = max(0, $weight_kg - 0.5);

		return [
			'pac' => [
				'title' => $this->language->get('text_pac'),
				'cost'  => round($row['pac']['base'] + ($extra * $row['pac']['per_kg']), 2)
			],
			'sedex' => [
				'title' => $this->language->get('text_sedex'),
				'cost'  => round($row['sedex']['base'] + ($extra * $row['sedex']['per_kg']), 2)
			],
		];
	}
}
