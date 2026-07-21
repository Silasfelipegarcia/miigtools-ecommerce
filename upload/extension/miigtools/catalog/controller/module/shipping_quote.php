<?php
namespace Opencart\Catalog\Controller\Extension\Miigtools\Module;
/**
 * CEP shipping quote for product page (no cart required).
 */
class ShippingQuote extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('extension/miigtools/module/shipping_quote');

		$json = [];
		$postcode = preg_replace('/\D+/', '', (string)($this->request->post['postcode'] ?? ''));

		if (strlen($postcode) !== 8) {
			$json['error'] = $this->language->get('error_postcode');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));

			return;
		}

		$zone_id = (int)($this->request->post['zone_id'] ?? 0);
		$country_id = (int)($this->request->post['country_id'] ?? $this->config->get('config_country_id') ?: 30);
		$uf = strtoupper((string)($this->request->post['zone_code'] ?? ''));
		$city = (string)($this->request->post['city'] ?? '');

		if ($uf === '' || $city === '') {
			$via = $this->lookupViaCep($postcode);

			if ($via) {
				if ($uf === '') {
					$uf = $via['uf'];
				}

				if ($city === '') {
					$city = $via['city'];
				}
			}
		}

		if (!$zone_id && $uf !== '') {
			$q = $this->db->query(
				"SELECT `zone_id` FROM `" . DB_PREFIX . "zone` WHERE `country_id` = '" . (int)$country_id . "' AND `code` = '" . $this->db->escape($uf) . "' LIMIT 1"
			);

			if ($q->num_rows) {
				$zone_id = (int)$q->row['zone_id'];
			}
		}

		$address = [
			'firstname'      => '',
			'lastname'       => '',
			'company'        => '',
			'address_1'      => '',
			'address_2'      => '',
			'postcode'       => $postcode,
			'city'           => $city,
			'zone_id'        => $zone_id,
			'zone'           => '',
			'zone_code'      => $uf,
			'country_id'     => $country_id,
			'country'        => 'Brasil',
			'iso_code_2'     => 'BR',
			'iso_code_3'     => 'BRA',
			'address_format' => '',
			'custom_field'   => []
		];

		$this->load->model('checkout/shipping_method');
		$quote_data = $this->model_checkout_shipping_method->getMethods($address);

		$quotes = [];

		foreach ($quote_data as $method) {
			if (empty($method['quote'])) {
				continue;
			}

			foreach ($method['quote'] as $quote) {
				$quotes[] = [
					'title'      => $quote['title'] ?? ($method['title'] ?? ''),
					'text'       => $quote['text'] ?? '',
					'cost'       => $quote['cost'] ?? 0,
					'code'       => $quote['code'] ?? '',
					'eta'        => $this->estimateEta($uf)
				];
			}
		}

		if (!$quotes) {
			// Fallback estimate when no shipping extension returns quotes without cart weight
			$cost = $this->fallbackCost($uf);
			$quotes[] = [
				'title' => $this->language->get('text_estimate'),
				'text'  => $this->currency->format($cost, $this->session->data['currency'] ?? $this->config->get('config_currency')),
				'cost'  => $cost,
				'code'  => 'estimate.standard',
				'eta'   => $this->estimateEta($uf)
			];
		}

		$json['quotes'] = $quotes;
		$json['city'] = $city;
		$json['zone_code'] = $uf;
		$json['hint'] = $this->language->get('text_stock_cutoff');
		$json['success'] = true;

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * @return array{uf: string, city: string}|null
	 */
	private function lookupViaCep(string $postcode): ?array {
		$url = 'https://viacep.com.br/ws/' . $postcode . '/json/';
		$raw = false;

		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt_array($ch, [
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT        => 4,
				CURLOPT_CONNECTTIMEOUT => 3,
			]);
			$raw = curl_exec($ch);
			curl_close($ch);
		} else {
			$raw = @file_get_contents($url);
		}

		if (!$raw) {
			return null;
		}

		$data = json_decode($raw, true);

		if (!is_array($data) || !empty($data['erro'])) {
			return null;
		}

		return [
			'uf'   => strtoupper((string)($data['uf'] ?? '')),
			'city' => (string)($data['localidade'] ?? '')
		];
	}

	private function estimateEta(string $uf): string {
		$uf = strtoupper($uf);
		$near = ['SP', 'RJ', 'MG', 'PR', 'SC', 'RS', 'ES'];

		if (in_array($uf, $near, true)) {
			return sprintf($this->language->get('text_eta_days'), '2–5');
		}

		if ($uf === '') {
			return sprintf($this->language->get('text_eta_days'), '5–12');
		}

		return sprintf($this->language->get('text_eta_days'), '5–10');
	}

	private function fallbackCost(string $uf): float {
		$uf = strtoupper($uf);
		$near = ['SP', 'RJ', 'MG', 'PR', 'SC', 'RS', 'ES'];

		if (in_array($uf, $near, true)) {
			return 25.0;
		}

		if ($uf === '') {
			return 45.0;
		}

		return 39.0;
	}
}
