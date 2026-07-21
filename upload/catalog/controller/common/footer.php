<?php
namespace Opencart\Catalog\Controller\Common;
/**
 * Class Footer
 *
 * Can be called from $this->load->controller('common/footer');
 *
 * @package Opencart\Catalog\Controller\Common
 */
class Footer extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return string
	 */
	public function index(): string {
		$this->load->language('common/footer');

		// Article
		$this->load->model('cms/article');

		$article_total = $this->model_cms_article->getTotalArticles();

		if ($article_total) {
			$data['blog'] = $this->url->link('cms/blog', 'language=' . $this->config->get('config_language'));
		} else {
			$data['blog'] = '';
		}

		// Information
		$data['informations'] = [];

		$this->load->model('catalog/information');

		$results = $this->model_catalog_information->getInformations();

		foreach ($results as $result) {
			// Trocas e Devoluções fica só em “Serviços ao cliente” (link do form).
			if ((int)$result['information_id'] === 6) {
				continue;
			}

			// Landings (10–15) e FAQ (16) ficam no bloco “Por aplicação” / FAQ
			$id = (int)$result['information_id'];

			if ($id >= 10 && $id <= 16) {
				continue;
			}

			$data['informations'][] = ['href' => $this->url->link('information/information', 'language=' . $this->config->get('config_language') . '&information_id=' . $result['information_id'])] + $result;
		}

		$data['contact'] = $this->url->link('information/contact', 'language=' . $this->config->get('config_language'));
		$data['return'] = $this->url->link('account/returns.add', 'language=' . $this->config->get('config_language'));

		if ($this->config->get('config_gdpr_id')) {
			$data['gdpr'] = $this->url->link('information/gdpr', 'language=' . $this->config->get('config_language'));
		} else {
			$data['gdpr'] = '';
		}

		$data['sitemap'] = $this->url->link('information/sitemap', 'language=' . $this->config->get('config_language'));
		$data['manufacturer'] = $this->url->link('product/manufacturer', 'language=' . $this->config->get('config_language'));

		if ($this->config->get('config_affiliate_status')) {
			$data['affiliate'] = $this->url->link('account/affiliate', 'language=' . $this->config->get('config_language') . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		} else {
			$data['affiliate'] = '';
		}

		$data['special'] = $this->url->link('product/special', 'language=' . $this->config->get('config_language') . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		$data['account'] = $this->url->link('account/account', 'language=' . $this->config->get('config_language') . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		$data['order'] = $this->url->link('account/order', 'language=' . $this->config->get('config_language') . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		$data['wishlist'] = $this->url->link('account/wishlist', 'language=' . $this->config->get('config_language') . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		$data['newsletter'] = $this->url->link('account/newsletter', 'language=' . $this->config->get('config_language') . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));

		$data['powered'] = sprintf($this->language->get('text_powered'), $this->config->get('config_name'), date('Y', time()));

		// Who's Online
		if ($this->config->get('config_customer_online')) {
			$this->load->model('tool/online');

			if (isset($this->request->server['HTTP_HOST']) && isset($this->request->server['REQUEST_URI'])) {
				$url = ($this->request->server['HTTPS'] ? 'https://' : 'http://') . $this->request->server['HTTP_HOST'] . $this->request->server['REQUEST_URI'];
			} else {
				$url = '';
			}

			if (isset($this->request->server['HTTP_REFERER'])) {
				$referer = $this->request->server['HTTP_REFERER'];
			} else {
				$referer = '';
			}

			$this->model_tool_online->addOnline(oc_get_ip(), $this->customer->getId(), $url, $referer);
		}

		$data['bootstrap'] = 'catalog/view/javascript/bootstrap/js/bootstrap.bundle.min.js';
		$data['scripts'] = $this->document->getScripts('footer');
		$data['cookie'] = $this->load->controller('common/cookie');

		// Floating WhatsApp contact
		$data['whatsapp_url'] = 'https://wa.me/551122360122?text=' . rawurlencode('Olá! Vim pelo site da MIIGTOOLS e gostaria de mais informações.');
		$data['whatsapp_label'] = $this->language->get('text_whatsapp');
		$data['text_pay_title'] = $this->language->get('text_pay_title');
		$data['text_pay_pix'] = $this->language->get('text_pay_pix');
		$data['text_pay_mp'] = $this->language->get('text_pay_mp');
		$data['text_pay_card'] = $this->language->get('text_pay_card');
		$data['text_pay_transfer'] = $this->language->get('text_pay_transfer');
		$data['text_ship_br'] = $this->language->get('text_ship_br');
		$data['applications'] = $this->getApplicationLinks();
		$data['text_applications'] = $this->language->get('text_applications');
		$data['faq_href'] = $this->url->link('information/information', 'language=' . $this->config->get('config_language') . '&information_id=16');
		$data['text_faq'] = $this->language->get('text_faq');

		return $this->load->view('common/footer', $data);
	}

	/**
	 * @return list<array{title: string, href: string}>
	 */
	private function getApplicationLinks(): array {
		$lang = $this->config->get('config_language');
		$pages = [
			[10, 'Machos para aço'],
			[11, 'Bits para torno'],
			[12, 'Pontas rotativas CM'],
			[13, 'Ferramentas DIN'],
			[14, 'Alargadores H7'],
			[15, 'Porta-ferramentas'],
		];

		$links = [];

		foreach ($pages as [$id, $fallback]) {
			$links[] = [
				'title' => $fallback,
				'href'  => $this->url->link('information/information', 'language=' . $lang . '&information_id=' . $id)
			];
		}

		return $links;
	}
}
