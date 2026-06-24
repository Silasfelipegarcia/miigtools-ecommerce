<?php
namespace Opencart\Admin\Controller\Extension\Miigtools\Payment;
/**
 * Class Mercadopago
 */
class Mercadopago extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('extension/miigtools/payment/mercadopago');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/miigtools/payment/mercadopago', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/miigtools/payment/mercadopago.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment');

		$fields = [
			'payment_mercadopago_environment',
			'payment_mercadopago_public_key',
			'payment_mercadopago_access_token',
			'payment_mercadopago_public_key_test',
			'payment_mercadopago_access_token_test',
			'payment_mercadopago_order_status_pending',
			'payment_mercadopago_order_status_approved',
			'payment_mercadopago_order_status_rejected',
			'payment_mercadopago_order_status_refunded',
			'payment_mercadopago_geo_zone_id',
			'payment_mercadopago_status',
			'payment_mercadopago_sort_order'
		];

		foreach ($fields as $field) {
			$data[$field] = $this->config->get($field);
		}

		$this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$this->load->model('localisation/geo_zone');
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/miigtools/payment/mercadopago', $data));
	}

	public function save(): void {
		$this->load->language('extension/miigtools/payment/mercadopago');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/miigtools/payment/mercadopago')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('setting/setting');
			$this->model_setting_setting->editSetting('payment_mercadopago', $this->request->post);
			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
