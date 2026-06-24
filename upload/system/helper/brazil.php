<?php
/**
 * Brazilian document and phone helpers.
 */

function oc_brazil_digits(string $value): string {
	return preg_replace('/\D/', '', $value);
}

function oc_validate_cpf(string $cpf): bool {
	$cpf = oc_brazil_digits($cpf);

	if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
		return false;
	}

	for ($t = 9; $t < 11; $t++) {
		$sum = 0;

		for ($i = 0; $i < $t; $i++) {
			$sum += (int)$cpf[$i] * (($t + 1) - $i);
		}

		$digit = ((10 * $sum) % 11) % 10;

		if ((int)$cpf[$t] !== $digit) {
			return false;
		}
	}

	return true;
}

function oc_validate_cnpj(string $cnpj): bool {
	$cnpj = oc_brazil_digits($cnpj);

	if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
		return false;
	}

	$weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
	$weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

	$sum = 0;

	for ($i = 0; $i < 12; $i++) {
		$sum += (int)$cnpj[$i] * $weights1[$i];
	}

	$digit = $sum % 11;
	$digit = ($digit < 2) ? 0 : 11 - $digit;

	if ((int)$cnpj[12] !== $digit) {
		return false;
	}

	$sum = 0;

	for ($i = 0; $i < 13; $i++) {
		$sum += (int)$cnpj[$i] * $weights2[$i];
	}

	$digit = $sum % 11;
	$digit = ($digit < 2) ? 0 : 11 - $digit;

	return (int)$cnpj[13] === $digit;
}

function oc_validate_brazil_document(string $type, string $number): bool {
	$type = strtoupper($type);

	if ($type === 'CPF') {
		return oc_validate_cpf($number);
	}

	if ($type === 'CNPJ') {
		return oc_validate_cnpj($number);
	}

	return false;
}

function oc_parse_brazil_phone(string $telephone): array {
	$digits = oc_brazil_digits($telephone);

	if (str_starts_with($digits, '55') && strlen($digits) >= 12) {
		$digits = substr($digits, 2);
	}

	if (strlen($digits) < 10) {
		return ['area_code' => '', 'number' => $digits];
	}

	$area_code = substr($digits, 0, 2);
	$number = substr($digits, 2);

	return ['area_code' => $area_code, 'number' => $number];
}

function oc_split_street_address(string $address): array {
	$address = trim($address);

	if (preg_match('/^(.*?)[,\s]+(\d+\w*)$/', $address, $matches)) {
		return [
			'street_name'   => trim($matches[1]),
			'street_number' => trim($matches[2])
		];
	}

	return [
		'street_name'   => $address,
		'street_number' => 'S/N'
	];
}
