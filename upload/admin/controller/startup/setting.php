<?php
namespace Opencart\Admin\Controller\Startup;
/**
 * Class Setting
 *
 * @package Opencart\Admin\Controller\Startup
 */
class Setting extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		// Setting
		$this->load->model('setting/setting');

		$results = $this->model_setting_setting->getSettings(0);

		foreach ($results as $result) {
			if (!$result['serialized']) {
				$this->config->set($result['key'], $result['value']);
			} else {
				$this->config->set($result['key'], json_decode($result['value'], true));
			}
		}

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

		// Always serve admin assets from the host the visitor is actually using.
		// Prevents CORS (e.g. Font Awesome) when HTTP_SERVER points at a domain
		// that is not live yet (www.miigtools.com.br) while admin runs on Railway.
		$forwarded_proto = '';

		if (!empty($this->request->server['HTTP_X_FORWARDED_PROTO'])) {
			$forwarded_proto = strtolower((string)$this->request->server['HTTP_X_FORWARDED_PROTO']);
		}

		$https = (!empty($this->request->server['HTTPS']) && $this->request->server['HTTPS'] !== 'off')
			|| $forwarded_proto === 'https';

		$host = (string)($this->request->server['HTTP_HOST'] ?? '');

		if ($host !== '') {
			$catalog = ($https ? 'https://' : 'http://') . $host . '/';
			$admin = $catalog . 'admin/';

			$this->config->set('site_url', $admin);
			$this->config->set('config_url', $catalog);
			$this->config->set('config_secure', $catalog);
		}
	}
}
