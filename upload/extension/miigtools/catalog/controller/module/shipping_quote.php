<?php
namespace Opencart\Catalog\Controller\Extension\Miigtools\Module;
/**
 * Cotação de frete estilo Correios (PAC/SEDEX) a partir de Imirim, São Paulo.
 * Não usa o frete flat R$ 5 do OpenCart — estima por faixa de CEP/UF como o ML.
 */
class ShippingQuote extends \Opencart\System\Engine\Controller {
	/** CEP de origem: Imirim, São Paulo/SP */
	private const ORIGIN_POSTCODE = '02465000';
	private const ORIGIN_LABEL = 'Imirim, São Paulo/SP';

	/** Peso padrão (kg) para cotação no PDP sem carrinho — ferramentas leves */
	private const DEFAULT_WEIGHT_KG = 0.5;

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
		$band = $this->distanceBand($postcode, $uf);
		$quotes = $this->buildCorreiosQuotes($band, $weight);

		$currency = $this->session->data['currency'] ?? $this->config->get('config_currency');

		foreach ($quotes as &$quote) {
			$quote['text'] = $this->currency->format($quote['cost'], $currency);
		}
		unset($quote);

		$json['quotes'] = $quotes;
		$json['city'] = $city;
		$json['zone_code'] = $uf;
		$json['origin'] = self::ORIGIN_LABEL;
		$json['weight_kg'] = $weight;
		$json['hint'] = sprintf($this->language->get('text_origin_hint'), self::ORIGIN_LABEL) . ' ' . $this->language->get('text_stock_cutoff');
		$json['success'] = true;

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function resolveWeightKg(): float {
		$posted = (float)($this->request->post['weight'] ?? 0);

		if ($posted > 0) {
			return max(0.3, min(30.0, $posted));
		}

		if ($this->cart->hasProducts()) {
			$weight = (float)$this->cart->getWeight();

			if ($weight > 0) {
				// OpenCart weight class: assume kg if value looks like kg, else convert grams
				if ($weight > 50) {
					$weight = $weight / 1000;
				}

				return max(0.3, min(30.0, $weight));
			}
		}

		return self::DEFAULT_WEIGHT_KG;
	}

	/**
	 * Faixas inspiradas em frete Correios a partir da capital SP (Imirim).
	 *
	 * @return 'local'|'near'|'mid'|'far'
	 */
	private function distanceBand(string $postcode, string $uf): string {
		$uf = strtoupper($uf);
		$prefix = (int)substr($postcode, 0, 2);

		// Grande São Paulo / capital (CEPs 01xxx–05xxx e parte da RM)
		if ($uf === 'SP' && $prefix >= 1 && $prefix <= 5) {
			return 'local';
		}

		// Interior SP
		if ($uf === 'SP') {
			return 'near';
		}

		$near = ['RJ', 'MG', 'PR', 'ES'];
		$mid = ['SC', 'RS', 'GO', 'DF', 'MS', 'MT', 'BA'];

		if (in_array($uf, $near, true)) {
			return 'near';
		}

		if (in_array($uf, $mid, true)) {
			return 'mid';
		}

		return 'far';
	}

	/**
	 * Tabela aproximada PAC/SEDEX (valores de balcão Correios, origem SP capital).
	 * Fórmula: base por faixa + adicional por kg acima de 0,5 kg.
	 *
	 * @return list<array{title: string, cost: float, code: string, eta: string}>
	 */
	private function buildCorreiosQuotes(string $band, float $weight_kg): array {
		$table = [
			'local' => [
				'pac'   => ['base' => 22.90, 'per_kg' => 4.50, 'days' => '2–4'],
				'sedex' => ['base' => 32.50, 'per_kg' => 6.80, 'days' => '1–2'],
			],
			'near'  => [
				'pac'   => ['base' => 34.90, 'per_kg' => 7.20, 'days' => '3–6'],
				'sedex' => ['base' => 48.90, 'per_kg' => 9.50, 'days' => '1–3'],
			],
			'mid'   => [
				'pac'   => ['base' => 48.50, 'per_kg' => 9.80, 'days' => '5–9'],
				'sedex' => ['base' => 68.90, 'per_kg' => 12.50, 'days' => '2–4'],
			],
			'far'   => [
				'pac'   => ['base' => 62.90, 'per_kg' => 12.00, 'days' => '7–12'],
				'sedex' => ['base' => 89.90, 'per_kg' => 15.50, 'days' => '3–6'],
			],
		];

		$row = $table[$band] ?? $table['mid'];
		$extra_kg = max(0, $weight_kg - 0.5);

		$pac_cost = round($row['pac']['base'] + ($extra_kg * $row['pac']['per_kg']), 2);
		$sedex_cost = round($row['sedex']['base'] + ($extra_kg * $row['sedex']['per_kg']), 2);

		return [
			[
				'title' => $this->language->get('text_pac'),
				'cost'  => $pac_cost,
				'code'  => 'correios.pac',
				'eta'   => sprintf($this->language->get('text_eta_days'), $row['pac']['days'])
			],
			[
				'title' => $this->language->get('text_sedex'),
				'cost'  => $sedex_cost,
				'code'  => 'correios.sedex',
				'eta'   => sprintf($this->language->get('text_eta_days'), $row['sedex']['days'])
			],
		];
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
