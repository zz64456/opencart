<?php
namespace Opencart\Admin\Controller\Extension\OsecLanguage\Language;
class TraditionalChinese extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('extension/osec_language/language/traditional_chinese');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=language')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/osec_language/language/traditional_chinese', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/osec_language/language/traditional_chinese.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=language');

		$data['language_traditional_chinese_status'] = $this->config->get('language_traditional_chinese_status');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/osec_language/language/traditional_chinese', $data));
	}

	public function save(): void {
		$this->load->language('extension/osec_language/language/traditional_chinese');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/osec_language/language/traditional_chinese')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('language_traditional_chinese', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		if ($this->user->hasPermission('modify', 'extension/language')) {
			$language_info = $this->model_localisation_language->getLanguageByCode('zh-TW');

			if (!$language_info) {
				// Add language
				$language_data = [
					'name'       => '台灣正體中文',
					'code'       => 'zh-TW',
					'locale'     => 'zh-TW',
					'extension'  => 'osec_language',
					'status'     => 1,
					'sort_order' => 2
				];
				
				$this->load->model('localisation/language');

				$language_id = $this->model_localisation_language->addLanguage($language_data);

				$this->load->model('extension/osec_language/language/traditional_chinese');
				$this->model_extension_osec_language_language_traditional_chinese->install($language_id);

			} else {
				// Edit language
				$this->load->model('localisation/language');

				$this->model_localisation_language->editLanguage($language_info['language_id'], $language_info + ['extension' => 'osec_language']);
			}
		}
	}

	public function uninstall(): void {
		if ($this->user->hasPermission('modify', 'extension/language')) {
			$this->load->model('localisation/language');

			$language_info = $this->model_localisation_language->getLanguageByCode('zh-TW');

			if ($language_info) {
				$this->model_localisation_language->deleteLanguage($language_info['language_id']);
			}
		}
	}
}