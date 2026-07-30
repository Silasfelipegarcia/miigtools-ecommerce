<?php
namespace Opencart\Catalog\Controller\Information;
/**
 * Class Information
 *
 * @package Opencart\Catalog\Controller\Information
 */
class Information extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return ?\Opencart\System\Engine\Action
	 */
	public function index(): ?\Opencart\System\Engine\Action {
		$this->load->language('information/information');

		if (isset($this->request->get['information_id'])) {
			$information_id = (int)$this->request->get['information_id'];
		} else {
			$information_id = 0;
		}

		$this->load->model('catalog/information');

		$information_info = $this->model_catalog_information->getInformation($information_id);

		if ($information_info) {
			$this->document->setTitle($information_info['meta_title']);
			$this->document->setDescription($information_info['meta_description']);
			$this->document->setKeywords($information_info['meta_keyword']);

			$data['breadcrumbs'] = [];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
			];

			$info_url = $this->url->link('information/information', 'language=' . $this->config->get('config_language') . '&information_id=' . $information_id);

			$data['breadcrumbs'][] = [
				'text' => $information_info['title'],
				'href' => $info_url
			];

			$data['heading_title'] = $information_info['title'];

			$data['description'] = html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8');

			$data['continue'] = $this->url->link('common/home', 'language=' . $this->config->get('config_language'));

			$this->load->helper('miig_seo');
			miig_seo_add_json_ld($this->document, miig_seo_breadcrumb_schema($data['breadcrumbs']));
			miig_seo_set_open_graph($this->document, [
				'title'       => $information_info['meta_title'] ?: $information_info['title'],
				'description' => $information_info['meta_description'] ?: mb_substr(strip_tags($data['description']), 0, 160),
				'url'         => html_entity_decode($info_url, ENT_QUOTES, 'UTF-8'),
				'type'        => 'article',
			]);

			// FAQ técnico (id 16): FAQPage schema from stable Q&As
			if ($information_id === 16) {
				$faq_schema = miig_seo_faq_schema(array_merge(miig_seo_default_product_faqs(), [
					[
						'question' => 'O que é cone Morse (CM)?',
						'answer'   => 'CM2–CM5 identificam o cone da ponta rotativa ou bucha. Confira o fuso da máquina antes de comprar.',
					],
					[
						'question' => 'Como consultar frete e prazo?',
						'answer'   => 'Informe o CEP na página do produto para ver PAC/SEDEX. Pedidos com estoque confirmados até 14h (SP) seguem no mesmo dia útil.',
					],
				]));

				if ($faq_schema) {
					miig_seo_add_json_ld($this->document, $faq_schema);
				}
			}

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('information/information', $data));
		} else {
			return new \Opencart\System\Engine\Action('error/not_found');
		}

		return null;
	}

	/**
	 * Info
	 *
	 * @return void
	 */
	public function info(): void {
		if (isset($this->request->get['information_id'])) {
			$information_id = (int)$this->request->get['information_id'];
		} else {
			$information_id = 0;
		}

		$this->load->model('catalog/information');

		$information_info = $this->model_catalog_information->getInformation($information_id);

		if ($information_info) {
			$data['title'] = $information_info['title'];
			$data['description'] = html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8');

			$this->response->addHeader('X-Robots-Tag: noindex');
			$this->response->setOutput($this->load->view('information/information_info', $data));
		}
	}
}
