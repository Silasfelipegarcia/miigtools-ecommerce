<?php
namespace Opencart\Catalog\Controller\Startup;
/**
 * Class Setting
 *
 * @package Opencart\Catalog\Controller\Startup
 */
class Setting extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$forwarded_proto = '';

		if (!empty($this->request->server['HTTP_X_FORWARDED_PROTO'])) {
			$forwarded_proto = strtolower((string)$this->request->server['HTTP_X_FORWARDED_PROTO']);
		}

		$https = (!empty($this->request->server['HTTPS']) && $this->request->server['HTTPS'] !== 'off')
			|| $forwarded_proto === 'https';

		$host = (string)($this->request->server['HTTP_HOST'] ?? 'localhost');
		$hostname = ($https ? 'https://' : 'http://') . $host . '/';

		// Store
		$this->load->model('setting/store');

		$store_info = $this->model_setting_store->getStoreByHostname($hostname);

		if (isset($this->request->get['store_id'])) {
			$this->config->set('config_store_id', (int)$this->request->get['store_id']);
		} elseif ($store_info) {
			$this->config->set('config_store_id', $store_info['store_id']);
		} else {
			$this->config->set('config_store_id', 0);
		}

		if (!$store_info) {
			// If catalog constant is defined
			if (defined('HTTP_CATALOG')) {
				$this->config->set('config_url', HTTP_CATALOG);
			} else {
				$this->config->set('config_url', HTTP_SERVER);
			}
		}

		// Setting
		$this->load->model('setting/setting');

		$results = $this->model_setting_setting->getSettings((int)$this->config->get('config_store_id'));

		foreach ($results as $result) {
			if (!$result['serialized']) {
				$this->config->set($result['key'], $result['value']);
			} else {
				$this->config->set($result['key'], json_decode($result['value'], true));
			}
		}

		// Always serve assets from the host the visitor is actually using.
		// Prevents broken CSS/JS when config_url points to a domain that is not live yet
		// (e.g. www.miigtools.com.br) while the site is still on Railway.
		if ($host !== '') {
			$this->config->set('config_url', $hostname);
			$this->config->set('config_secure', $hostname);
		}

		// Url
		$this->registry->set('url', new \Opencart\System\Library\Url($this->config->get('config_url')));

		// Set time zone
		if ($this->config->get('config_timezone')) {
			date_default_timezone_set($this->config->get('config_timezone'));

			// Sync PHP and DB time zones.
			$this->db->query("SET `time_zone` = '" . $this->db->escape(date('P')) . "'");
		}

		// Response output compression level
		if ($this->config->get('config_compression')) {
			$this->response->setCompression((int)$this->config->get('config_compression'));
		}
	}
}
