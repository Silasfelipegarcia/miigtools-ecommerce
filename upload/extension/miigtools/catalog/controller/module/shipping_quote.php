<?php
namespace Opencart\Catalog\Controller\Extension\Miigtools\Module;
/**
 * Cotação de frete estilo Correios (PAC/SEDEX) a partir de Imirim, São Paulo.
 * Usa helper compartilhado com o modelo de checkout.
 */
class ShippingQuote extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('extension/miigtools/module/shipping_quote');
		$this->load->helper('miig_correios');

		$json = [];
		$postcode = preg_replace('/\D+/', '', (string)($this->request->post['postcode'] ?? ''));

		if (strlen($postcode) !== 8) {
			$json['error'] = $this->language->get('error_postcode');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));

			return;
		}

		$via = $this->lookupViaCep($postcode);
		$uf = $via['uf'] ?? '';
		$city = $via['city'] ?? '';

		if ($uf === '') {
			$json['error'] = $this->language->get('error_postcode_lookup');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));

			return;
		}

		$weight = $this->resolveWeightKg();
		$band = miig_correios_distance_band($postcode, $uf);
		$quotes = miig_correios_quotes($band, $weight);

		$currency = $this->session->data['currency'] ?? $this->config->get('config_currency');

		foreach ($quotes as &$quote) {
			$code = str_replace('correios.', '', $quote['code']);
			$days = preg_replace('/\s*dias úteis$/u', '', $quote['eta']);
			$quote['title'] = $code === 'sedex'
				? $this->language->get('text_sedex')
				: $this->language->get('text_pac');
			$quote['code'] = 'correios.' . $code;
			$quote['eta'] = sprintf($this->language->get('text_eta_days'), $days);
			$quote['text'] = $this->currency->format($quote['cost'], $currency);
		}
		unset($quote);

		$json['quotes'] = $quotes;
		$json['city'] = $city;
		$json['zone_code'] = $uf;
		$json['origin'] = miig_correios_origin_label();
		$json['weight_kg'] = $weight;
		$json['hint'] = sprintf($this->language->get('text_origin_hint'), miig_correios_origin_label()) . ' ' . $this->language->get('text_stock_cutoff');
		$json['success'] = true;

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function resolveWeightKg(): float {
		$this->load->helper('miig_correios');

		$posted = (float)($this->request->post['weight'] ?? 0);

		if ($posted > 0) {
			return miig_correios_normalize_weight($posted);
		}

		if ($this->cart->hasProducts()) {
			return miig_correios_normalize_weight((float)$this->cart->getWeight());
		}

		return miig_correios_normalize_weight(0.5);
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
}
