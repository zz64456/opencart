<?php
/*
	$Project$
	$Author$

	$Version$ ($Revision$)

	This is a controller class for a basic module settings page.
*/
namespace extension\ka_extensions;

abstract class ControllerSettings extends ControllerInstaller {

	use TraitControllerForm;

	protected function onLoad() {	

		$this->load->language('extension/ka_extensions/common');	
		$this->load->language('extension/ka_extensions/settings');
		$this->load->language('extension/' . $this->ext_code . '/extension');

		$this->fields = $this->getFields();
		
		parent::onLoad();
	}

	abstract protected function getFields();
	
	public function index() {

		$heading_title = $this->getTitle();
		$this->document->setTitle($heading_title);

		// get original field values
		$values = array();
		foreach ($this->fields as $k => $v) {
			$values[$v['code']] = $this->config->get($v['code']);
		}
		
		$fields = $this->getFieldsWithData($this->fields, $values);
		
		$this->data['fields'] = $fields;
		
		$this->data['heading_title'] = $heading_title;

		$this->data['extension_version'] = $this->extension_version;
		
		$this->data['breadcrumbs'] = array();
   		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']),
			'separator' => false
		);

  		$this->data['breadcrumbs'][] = array(
	 		'text'      => $this->language->get('Ka Extensions'),
			'href'      => $this->url->link('extension/ka_extensions/extensions', '&user_token=' . $this->session->data['user_token']),
 		);
		
 		$this->data['breadcrumbs'][] = array(
	 		'text'      => $heading_title,
			'href'      => $this->url->link('extension/' . $this->ext_code . '/extension', '&user_token=' . $this->session->data['user_token']),
 		);
		
		$this->data['action_save']   = $this->url->linka('extension/' . $this->ext_code . '/extension|save');
		$this->data['action_cancel'] = $this->url->linka('extension/ka_extensions/extensions');

		$this->showPage('extension/ka_extensions/common/pages/settings');
	}
	
	
	public function save() {

		if ($this->request->server['REQUEST_METHOD'] != 'POST') {
			die('Wrong request method');
		}

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/' . $this->ext_code . '/extension')) {
			$json['error']['warning'] = $this->language->get('error_permission');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return false;
		}
		
		// get original field values
		$values = array();
		foreach ($this->fields as $k => $v) {
			$values[$v['code']] = $this->config->get($v['code']);
		}
		
		// validate the submitted values and fill in their errors if required
		//
		if ($this->validate()) {
			$fields = $this->getFieldsWithData($this->fields, $values, $this->request->post);
			
			$values = $this->getFieldValues($fields);
			
			$this->model_setting_setting->editSetting($this->ext_code, $values);
			$json['success'] = $this->language->get('txt_operation_successful');
		} else {
			foreach ($this->errors as $k => $v) {
				$json['error'][$k] = $v;
			}
			
			if (isset($json['error']) && !isset($json['error']['warning'])) {
				$json['error']['warning'] = $this->language->get('error_warning') . ' <br> ' . implode('<br>', $json['error']);
			}
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		
		if (empty($json['success'])) {
			return false;
		}
			
		return true;
	}


	public function install($params) {

		if (!parent::install($params)) {
			return false;
		} 

		$this->load->model('user/user_group');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/' . $this->ext_code . '/extension');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/' . $this->ext_code . '/extension');
		
		$fields = $this->getFields();
		
		$default = array();
		foreach ($fields as $k => $v) {
			if (isset($v['default_value'])) {
				$default[$v['code']] = $v['default_value'];
			}
		}
		
		$settings = $this->model_setting_setting->getSetting($this->ext_code);
		
		$settings = array_merge($default, $settings);
		
		$this->model_setting_setting->editSetting($this->ext_code, $settings);
		
		return true;
 	}

		
	public function uninstall($params) {
	
		if (is_null($params)) {
			throw new \Exception("This method can be called from the ka extensions page only");
		}
	
		$this->model_setting_setting->deleteSetting($this->ext_code);
		
		return parent::uninstall($params);
	}
	
	
	protected function validate() {
	
		if (!$this->validateFields($this->fields, $this->request->post)) {
			return false;
		}
	
		return true;
	}
	
}