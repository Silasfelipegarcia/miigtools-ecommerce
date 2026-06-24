<?php
namespace Opencart\Catalog\Controller\Extension\Miigtools\Payment;
/**
 * Class Mercadopago
 */
class Mercadopago extends \Opencart\System\Engine\Controller {
	public function index(): string {
		$this->load->language('extension/miigtools/payment/mercadopago');
		$this->load->model('extension/miigtools/payment/mercadopago');

		$data['language'] = $this->config->get('config_language');
		$data['public_key'] = $this->model_extension_miigtools_payment_mercadopago->getPublicKey();
		$data['amount'] = 0;
		$data['order_id'] = 0;
		$data['process_url'] = $this->url->link('extension/miigtools/payment/mercadopago.process', 'language=' . $this->config->get('config_language'), true);
		$data['status_url'] = $this->url->link('extension/miigtools/payment/mercadopago.status', 'language=' . $this->config->get('config_language'), true);
		$data['success_url'] = $this->url->link('checkout/success', 'language=' . $this->config->get('config_language'), true);

		if (isset($this->session->data['order_id'])) {
			$this->load->model('checkout/order');

			$order_info = $this->model_checkout_order->getOrder((int)$this->session->data['order_id']);

			if ($order_info) {
				$data['order_id'] = (int)$order_info['order_id'];
				$data['amount'] = round((float)$order_info['total'], 2);
			}
		}

		return $this->load->view('extension/miigtools/payment/mercadopago', $data);
	}

	public function process(): void {
		$this->load->language('extension/miigtools/payment/mercadopago');
		$this->load->model('extension/miigtools/payment/mercadopago');
		$this->load->model('extension/miigtools/payment/mercadopago_api');
		$this->load->model('checkout/order');

		$json = [];

		if (!isset($this->session->data['order_id'])) {
			$json['error'] = $this->language->get('error_order');
		}

		if (!isset($json['error']) && (!isset($this->session->data['payment_method']) || $this->session->data['payment_method']['code'] !== 'mercadopago.mercadopago')) {
			$json['error'] = $this->language->get('error_payment_method');
		}

		$order_id = (int)($this->session->data['order_id'] ?? 0);
		$order_info = $order_id ? $this->model_checkout_order->getOrder($order_id) : [];

		if (!isset($json['error']) && !$order_info) {
			$json['error'] = $this->language->get('error_order');
		}

		$access_token = $this->model_extension_miigtools_payment_mercadopago->getAccessToken();

		if (!isset($json['error']) && !$access_token) {
			$json['error'] = $this->language->get('error_credentials');
		}

		$form_data = json_decode(file_get_contents('php://input'), true);

		if (!isset($json['error']) && !is_array($form_data)) {
			$form_data = $this->request->post;
		}

		if (!isset($json['error']) && empty($form_data)) {
			$json['error'] = $this->language->get('error_payment');
		}

		if (!isset($json['error'])) {
			$payload = $this->buildPaymentPayload($order_info, $form_data);

			if ($payload['transaction_amount'] <= 0) {
				$json['error'] = $this->language->get('error_amount');
			} else {
				$response = $this->model_extension_miigtools_payment_mercadopago_api->createPayment($access_token, $payload);

				if (!empty($response['id'])) {
					$this->session->data['mercadopago_payment_id'] = (int)$response['id'];
					$this->updateOrderFromPayment($order_id, $response, false);

					$json['payment_id'] = (int)$response['id'];
					$json['status'] = $response['status'] ?? '';

					if (($response['status'] ?? '') === 'approved') {
						$json['redirect'] = $this->url->link('checkout/success', 'language=' . $this->config->get('config_language'), true);
					} elseif (($response['status'] ?? '') === 'pending' && ($response['payment_method_id'] ?? '') === 'pix') {
						$json['pix'] = [
							'qr_code' => $response['point_of_interaction']['transaction_data']['qr_code'] ?? '',
							'qr_code_base64' => $response['point_of_interaction']['transaction_data']['qr_code_base64'] ?? '',
							'ticket_url' => $response['point_of_interaction']['transaction_data']['ticket_url'] ?? ''
						];
					} elseif (in_array(($response['status'] ?? ''), ['rejected', 'cancelled'], true)) {
						$json['error'] = $response['status_detail'] ?? $this->language->get('error_payment');
					}
				} else {
					$json['error'] = $response['message'] ?? ($response['cause'][0]['description'] ?? $this->language->get('error_payment'));
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function status(): void {
		$this->load->model('extension/miigtools/payment/mercadopago');
		$this->load->model('extension/miigtools/payment/mercadopago_api');
		$this->load->model('checkout/order');

		$json = [];

		$payment_id = (int)($this->request->get['payment_id'] ?? $this->session->data['mercadopago_payment_id'] ?? 0);
		$order_id = (int)($this->session->data['order_id'] ?? 0);

		if (!$payment_id || !$order_id) {
			$json['error'] = 'invalid_request';
		} else {
			$access_token = $this->model_extension_miigtools_payment_mercadopago->getAccessToken();
			$response = $this->model_extension_miigtools_payment_mercadopago_api->getPayment($access_token, $payment_id);

			if (!empty($response['id'])) {
				$this->updateOrderFromPayment($order_id, $response, true);
				$json['status'] = $response['status'] ?? '';

				if (($response['status'] ?? '') === 'approved') {
					$json['redirect'] = $this->url->link('checkout/success', 'language=' . $this->config->get('config_language'), true);
				}
			} else {
				$json['error'] = 'not_found';
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function webhook(): void {
		$this->load->model('extension/miigtools/payment/mercadopago');
		$this->load->model('extension/miigtools/payment/mercadopago_api');
		$this->load->model('checkout/order');

		$payment_id = 0;

		if (isset($this->request->get['data_id'])) {
			$payment_id = (int)$this->request->get['data_id'];
		} elseif (isset($this->request->get['id'])) {
			$payment_id = (int)$this->request->get['id'];
		} else {
			$body = json_decode(file_get_contents('php://input'), true);

			if (is_array($body)) {
				if (!empty($body['data']['id'])) {
					$payment_id = (int)$body['data']['id'];
				} elseif (!empty($body['id'])) {
					$payment_id = (int)$body['id'];
				}
			}
		}

		if ($payment_id) {
			$access_token = $this->model_extension_miigtools_payment_mercadopago->getAccessToken();
			$response = $this->model_extension_miigtools_payment_mercadopago_api->getPayment($access_token, $payment_id);

			if (!empty($response['id']) && !empty($response['external_reference'])) {
				$this->updateOrderFromPayment((int)$response['external_reference'], $response, true);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode(['ok' => true]));
	}

	private function buildPaymentPayload(array $order_info, array $form_data): array {
		require_once(DIR_SYSTEM . 'helper/brazil.php');

		$custom_field = $order_info['custom_field'];

		if (is_string($custom_field)) {
			$custom_field = json_decode($custom_field, true) ?: [];
		}

		$payer = $form_data['payer'] ?? [];
		$identification = $payer['identification'] ?? [];

		$document_type = strtoupper((string)($identification['type'] ?? $custom_field['document_type'] ?? 'CPF'));
		$document_number = oc_brazil_digits((string)($identification['number'] ?? $custom_field['document_number'] ?? ''));

		$phone = oc_parse_brazil_phone((string)$order_info['telephone']);
		$street = oc_split_street_address((string)($order_info['payment_address_1'] ?: ''));

		$payload = [
			'transaction_amount' => round((float)$order_info['total'], 2),
			'description'        => 'Pedido #' . $order_info['order_id'],
			'installments'       => (int)($form_data['installments'] ?? 1),
			'payment_method_id'  => $form_data['payment_method_id'] ?? '',
			'external_reference' => (string)$order_info['order_id'],
			'notification_url'   => $this->url->link('extension/miigtools/payment/mercadopago.webhook', '', true),
			'payer'              => [
				'email'          => (string)($payer['email'] ?? $order_info['email']),
				'first_name'     => (string)($order_info['payment_firstname'] ?: $order_info['firstname']),
				'last_name'      => (string)($order_info['payment_lastname'] ?: $order_info['lastname']),
				'identification' => [
					'type'   => $document_type,
					'number' => $document_number
				]
			],
			'additional_info' => [
				'items' => $this->getOrderItems((int)$order_info['order_id']),
				'payer' => [
					'phone' => [
						'area_code' => $phone['area_code'],
						'number'    => $phone['number']
					],
					'address' => [
						'zip_code'      => oc_brazil_digits((string)$order_info['payment_postcode']),
						'street_name'   => $street['street_name'],
						'street_number' => $street['street_number']
					]
				]
			]
		];

		if (!empty($form_data['token'])) {
			$payload['token'] = $form_data['token'];
		}

		if (!empty($form_data['issuer_id'])) {
			$payload['issuer_id'] = $form_data['issuer_id'];
		}

		return $payload;
	}

	private function getOrderItems(int $order_id): array {
		$this->load->model('checkout/order');

		$products = $this->model_checkout_order->getProducts($order_id);
		$items = [];

		foreach ($products as $product) {
			$items[] = [
				'id'          => (string)$product['product_id'],
				'title'       => (string)$product['name'],
				'quantity'    => (int)$product['quantity'],
				'unit_price'  => round((float)$product['price'], 2),
				'category_id' => 'others'
			];
		}

		return $items;
	}

	private function updateOrderFromPayment(int $order_id, array $payment, bool $notify): void {
		$status = $payment['status'] ?? '';
		$order_status_id = 0;
		$comment = 'Mercado Pago #' . ($payment['id'] ?? '') . ' - ' . $status;

		switch ($status) {
			case 'approved':
				$order_status_id = (int)$this->config->get('payment_mercadopago_order_status_approved');
				break;
			case 'pending':
			case 'in_process':
				$order_status_id = (int)$this->config->get('payment_mercadopago_order_status_pending');
				break;
			case 'refunded':
			case 'charged_back':
				$order_status_id = (int)$this->config->get('payment_mercadopago_order_status_refunded');
				break;
			case 'rejected':
			case 'cancelled':
				$order_status_id = (int)$this->config->get('payment_mercadopago_order_status_rejected');
				break;
		}

		if (!$order_status_id) {
			return;
		}

		$order_info = $this->model_checkout_order->getOrder($order_id);

		if (!$order_info || (int)$order_info['order_status_id'] === $order_status_id) {
			return;
		}

		$this->model_checkout_order->addHistory($order_id, $order_status_id, $comment, $notify);
	}
}
