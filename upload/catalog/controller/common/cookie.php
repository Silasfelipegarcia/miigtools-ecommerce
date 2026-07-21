<?php
namespace Opencart\Catalog\Controller\Common;
/**
 * Class Cookie
 *
 * Can be called from $this->load->controller('common/cookie');
 *
 * @package Opencart\Catalog\Controller\Common
 */
class Cookie extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return string
	 */
	public function index(): string {
		if ($this->config->get('config_cookie_id') && !isset($this->request->cookie['policy'])) {
			// Information
			$this->load->model('catalog/information');

			$information_info = $this->model_catalog_information->getInformation((int)$this->config->get('config_cookie_id'));

			if ($information_info) {
				$this->load->language('common/cookie');

				$data['text_cookie'] = sprintf($this->language->get('text_cookie'), $this->url->link('information/information.info', 'language=' . $this->config->get('config_language') . '&information_id=' . $information_info['information_id']));

				$data['agree'] = $this->url->link('common/cookie.confirm', 'language=' . $this->config->get('config_language') . '&agree=1', true);
				$data['disagree'] = $this->url->link('common/cookie.confirm', 'language=' . $this->config->get('config_language') . '&agree=0', true);

				return $this->load->view('common/cookie', $data);
			}
		}

		return '';
	}

	/**
	 * Confirm
	 *
	 * @return void
	 */
	public function confirm(): void {
		$json = [];

		if (isset($this->request->get['agree'])) {
			$agree = (string)$this->request->get['agree'] === '1' ? '1' : '0';
		} else {
			$agree = '0';
		}

		$option = [
			'expires'  => time() + 60 * 60 * 24 * 365,
			'path'     => $this->config->get('session_path') ?: '/',
			'secure'   => !empty($this->request->server['HTTPS']) && $this->request->server['HTTPS'] !== 'off',
			'httponly' => false,
			'SameSite' => 'Lax'
		];

		setcookie('policy', $agree, $option);

		// Ensure request sees it for the rest of this response cycle
		$this->request->cookie['policy'] = $agree;

		$this->load->language('common/cookie');

		$json['success'] = $this->language->get('text_success');

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
