<?php
namespace Opencart\Catalog\Controller\Common;
/**
 * Class Home
 *
 * Can be called from $this->load->controller('common/home');
 *
 * @package Opencart\Catalog\Controller\Common
 */
class Home extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$description = $this->config->get('config_description');
		$language_id = $this->config->get('config_language_id');

		if (isset($description[$language_id])) {
			$this->document->setTitle($description[$language_id]['meta_title']);
			$this->document->setDescription($description[$language_id]['meta_description']);
			$this->document->setKeywords($description[$language_id]['meta_keyword']);
		}

		$this->load->language('common/home');

		$lang = $this->config->get('config_language');

		$data['hero_kicker'] = $this->language->get('text_hero_kicker');
		$data['hero_title'] = $this->language->get('text_hero_title');
		$data['hero_lead'] = $this->language->get('text_hero_lead');
		$data['hero_cta_catalog'] = $this->language->get('text_hero_cta_catalog');
		$data['hero_cta_contact'] = $this->language->get('text_hero_cta_contact');
		$data['strip_title'] = $this->language->get('text_strip_title');
		$data['strip_sub'] = $this->language->get('text_strip_sub');

		$data['hero_href_catalog'] = $this->url->link('product/category', 'language=' . $lang . '&path=59');
		$data['hero_href_contact'] = $this->url->link('information/contact', 'language=' . $lang);

		$data['value_props'] = [
			[
				'icon'  => 'fa-solid fa-fire-flame-curved',
				'title' => $this->language->get('text_vp_1_title'),
				'text'  => $this->language->get('text_vp_1_text'),
			],
			[
				'icon'  => 'fa-solid fa-boxes-stacked',
				'title' => $this->language->get('text_vp_2_title'),
				'text'  => $this->language->get('text_vp_2_text'),
			],
			[
				'icon'  => 'fa-solid fa-helmet-safety',
				'title' => $this->language->get('text_vp_3_title'),
				'text'  => $this->language->get('text_vp_3_text'),
			],
			[
				'icon'  => 'fa-solid fa-gauge-high',
				'title' => $this->language->get('text_vp_4_title'),
				'text'  => $this->language->get('text_vp_4_text'),
			],
		];

		$data['about_title'] = $this->language->get('text_about_title');
		$data['about_lead'] = $this->language->get('text_about_lead');
		$data['about_body'] = $this->language->get('text_about_body');
		$data['about_highlight'] = $this->language->get('text_about_highlight');
		$data['about_cta'] = $this->language->get('text_about_cta');
		$data['about_cta_info'] = $this->language->get('text_about_cta_info');
		$data['about_href_contact'] = $this->url->link('information/contact', 'language=' . $lang);
		$data['about_bullets'] = [
			$this->language->get('text_about_bullet_1'),
			$this->language->get('text_about_bullet_2'),
			$this->language->get('text_about_bullet_3'),
			$this->language->get('text_about_bullet_4'),
			$this->language->get('text_about_bullet_5'),
			$this->language->get('text_about_bullet_6'),
		];
		$data['about_stats'] = [
			[
				'value' => $this->language->get('text_about_stat_1_value'),
				'label' => $this->language->get('text_about_stat_1_label'),
				'icon'  => 'fa-solid fa-fire-flame-curved',
			],
			[
				'value' => $this->language->get('text_about_stat_2_value'),
				'label' => $this->language->get('text_about_stat_2_label'),
				'icon'  => 'fa-solid fa-helmet-safety',
			],
			[
				'value' => $this->language->get('text_about_stat_3_value'),
				'label' => $this->language->get('text_about_stat_3_label'),
				'icon'  => 'fa-solid fa-store',
			],
			[
				'value' => $this->language->get('text_about_stat_4_value'),
				'label' => $this->language->get('text_about_stat_4_label'),
				'icon'  => 'fa-solid fa-truck-fast',
				'flag'  => true,
			],
		];

		$this->load->model('catalog/information');

		$about_information_id = 5;

		$information_info = $this->model_catalog_information->getInformation($about_information_id);

		if ($information_info) {
			$data['about_href_info'] = $this->url->link('information/information', 'language=' . $lang . '&information_id=' . $about_information_id);
		} else {
			$data['about_href_info'] = '';
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/home', $data));
	}
}
