<?php
namespace Opencart\Admin\Controller\Common;
/**
 * Class Footer
 *
 * Can be loaded using $this->load->controller('common/footer');
 *
 * @package Opencart\Admin\Controller\Common
 */
class Footer extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return string
	 */
	public function index(): string {
		$this->load->language('common/footer');

		$data['text_footer'] = $this->language->get('text_footer');
		$data['text_version'] = '';

		$data['bootstrap'] = 'view/javascript/bootstrap/js/bootstrap.bundle.min.js';

		return $this->load->view('common/footer', $data);
	}
}
