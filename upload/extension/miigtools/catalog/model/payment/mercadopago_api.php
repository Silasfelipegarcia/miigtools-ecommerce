<?php
namespace Opencart\Catalog\Model\Extension\Miigtools\Payment;
/**
 * Mercado Pago REST API client.
 */
class MercadopagoApi extends \Opencart\System\Engine\Model {
	private const API_URL = 'https://api.mercadopago.com';

	public function createPayment(string $access_token, array $payload): array {
		return $this->request('POST', '/v1/payments', $access_token, $payload);
	}

	public function getPayment(string $access_token, int $payment_id): array {
		return $this->request('GET', '/v1/payments/' . $payment_id, $access_token);
	}

	private function request(string $method, string $path, string $access_token, array $payload = []): array {
		$curl = curl_init();

		$options = [
			CURLOPT_URL            => self::API_URL . $path,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_HTTPHEADER     => [
				'Content-Type: application/json',
				'Authorization: Bearer ' . $access_token
			]
		];

		if ($method === 'POST') {
			$options[CURLOPT_POST] = true;
			$options[CURLOPT_POSTFIELDS] = json_encode($payload);
		}

		curl_setopt_array($curl, $options);

		$response = curl_exec($curl);
		$http_code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$error = curl_error($curl);

		curl_close($curl);

		$data = json_decode((string)$response, true);

		if (!is_array($data)) {
			$data = [];
		}

		$data['_http_code'] = $http_code;

		if ($error) {
			$data['message'] = $error;
		}

		return $data;
	}
}
