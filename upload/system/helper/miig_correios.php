<?php
/**
 * Tabela compartilhada PAC/SEDEX (origem Imirim/SP) — PDP quote e checkout.
 */
function miig_correios_origin_postcode(): string {
	return '02465000';
}

function miig_correios_origin_label(): string {
	return 'Imirim, São Paulo/SP';
}

/**
 * @return 'local'|'near'|'mid'|'far'
 */
function miig_correios_distance_band(string $postcode, string $uf): string {
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
 * @return array{pac: array{base: float, per_kg: float, days: string}, sedex: array{base: float, per_kg: float, days: string}}
 */
function miig_correios_rate_row(string $band): array {
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

	return $table[$band] ?? $table['mid'];
}

/**
 * @return list<array{code: string, title: string, cost: float, eta: string}>
 */
function miig_correios_quotes(string $band, float $weight_kg): array {
	$row = miig_correios_rate_row($band);
	$extra = max(0, $weight_kg - 0.5);

	return [
		[
			'code'  => 'pac',
			'title' => 'PAC',
			'cost'  => round($row['pac']['base'] + ($extra * $row['pac']['per_kg']), 2),
			'eta'   => $row['pac']['days'] . ' dias úteis',
		],
		[
			'code'  => 'sedex',
			'title' => 'SEDEX',
			'cost'  => round($row['sedex']['base'] + ($extra * $row['sedex']['per_kg']), 2),
			'eta'   => $row['sedex']['days'] . ' dias úteis',
		],
	];
}

function miig_correios_normalize_weight(float $weight): float {
	if ($weight > 50) {
		$weight = $weight / 1000;
	}

	if ($weight <= 0) {
		$weight = 0.5;
	}

	return max(0.3, min(30.0, $weight));
}
