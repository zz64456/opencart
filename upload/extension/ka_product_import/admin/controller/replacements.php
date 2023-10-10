<?php
/*
	$Project: CSV Product Import $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 6.0.0.2 $ ($Revision: 581 $)
	
*/

namespace extension\ka_product_import;

class ControllerReplacements extends \extension\ka_extensions\ControllerForm { 

	private $kamodel_replacents;
	private $kamodel_import_groups;

	function onload() {
		// load langauge files
		$this->load->language('extension/ka_product_import/replacements');
		
		// load a model file
		$this->kamodel_replacements = $this->load->kamodel('extension/ka_product_import/replacements');
		$this->kamodel_import_group = $this->load->kamodel('extension/ka_product_import/import_group');
		
		parent::onLoad();
	}
	
  	protected function getPageUrlParams() {
  	
  		$params = array(
			'filter_text' => '',
			'filter_import_group_id' => 0,
			
			'sort'  => 'name',
			'order' => 'DESC', 
			'page'  => '1',
		);
		
		return $params;
	}
	
	
  	public function index() {
    	$this->getList();
  	}
  	
  	
  	public function save() {
  	
		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
		
			// remove sample record 
			//
			if (isset($this->request->post['replacements'][0])) {
				unset($this->request->post['replacements'][0]);
			}
			
		 	if ($this->validate()) {
				foreach ($this->request->post['replacements'] as $k => $v) {
					if (!empty($v['to_delete'])) {
						$this->kamodel_replacements->deleteReplacement($v['import_replacement_id']);
					} else {
						$this->kamodel_replacements->saveReplacement($v);
					}
		      	}
		 	
				$this->addTopMessage($this->language->get("txt_operation_successful"));
    	  	}
		}
		
  		$this->response->redirect($this->url->linka('extension/ka_product_import/replacements', $this->url_params->getUrl()));
  	}

  	  	
  	public function delete() {

		// remove sample record 
		//
		if (isset($this->request->post['replacements'][0])) {
			unset($this->request->post['replacements'][0]);
		}
  	
    	if (isset($this->request->post['selected']) && $this->validateModify()) {
			foreach ($this->request->post['selected'] as $import_replacement_id) {
				$this->kamodel_replacements->deleteReplacement($import_replacement_id);
			}
		}

		$this->addTopMessage($this->language->get("txt_operation_successful"));
		$this->response->redirect($this->url->linka('extension/ka_product_import/replacements', $this->url_params->getUrl(), true));
  	}


  	protected function getList() {

		$this->data['replacements'] = array();

		$params = $this->url_params->getParams();
		$params['start'] = ($params['page'] - 1) * $this->config->get('config_pagination_admin');
		$params['limit'] = $this->config->get('config_pagination_admin');
		
		$replacements_total = $this->kamodel_replacements->getReplacementsTotal($params);
		
		$results = $this->kamodel_replacements->getReplacements($params);

		$this->data['replacements'] = array(
			array(
				'import_replacement_id' => '',
				'import_group_id'       => '',
				'column_name'           => '',
				'old_value'             => '',
				'new_value'             => ''
			)
		);

		foreach ($results as $result) {
			$this->data['replacements'][] = array(
				'import_group_id' => $result['import_group_id'],
				'import_replacement_id'   => $result['import_replacement_id'],
				'column_name' => $result['column_name'],
				'old_value' => $result['old_value'],
				'new_value' => $result['new_value'],
			);
		}
		
		$this->pagination = new \extension\ka_extensions\Pagination(array(
			'total' => $replacements_total,
			'page'  => $params['page'],
			'limit' => $this->config->get('config_pagination_admin'),
			'url'   => $this->url->linka('extension/ka_product_import/replacements', $this->url_params->getUrl(['page' => '{page}']))
		));
		
		$this->data['sort_name'] = $this->url->linka('extension/ka_product_import/replacements', $this->url_params->getUrlSort('name'), true);

		$this->data['import_groups']      = $this->kamodel_import_group->getImportGroups();		
		$this->data['import_groups_page'] = $this->url->linka('extension/ka_product_import/import_group', 'user_token=' . $this->session->data['user_token']);
		
		// breacrumbs and title
		//
  		$this->document->setTitle($this->language->get('Import Replacements'));
   		$this->addBreadcrumb($this->language->get('Import Replacements'));
   		
		$this->data['action_filter'] = $this->url->linka('extension/ka_product_import/replacements');
		$this->data['action_save'] = $this->url->linka('extension/ka_product_import/replacements|save', $this->url_params->getUrl());
		$this->data['action_delete'] = $this->url->linka('extension/ka_product_import/replacements|delete', $this->url_params->getUrl());
		
		$this->data['params'] = $params;
		
		$this->showPage('extension/ka_product_import/replacements_list');
  	}
  	

	protected function getFields() {
	
		$fields = array(
			'import_group_id' => array(
				'code' => 'import_group_id',
				'type' => 'select',
				'required' => true,
			),
			'column_name' => array(
				'code' => 'column_name',
				'required' => true,
				'type' => 'text'
			),
			'old_value' => array(
				'code' => 'old_value',
				'required' => true,
				'type' => 'text'
			),
			'new_value' => array(
				'code' => 'new_value',
				'required' => true,
				'type' => 'text'
			),
		);
		
		$field['import_group_id']['options'] = $this->kamodel_import_group->getImportGroups();
		
		return $fields;
	}

	
	protected function validate() {
	
		if (!$this->validateModify()) {
			return false;
		}

		foreach ($this->request->post['replacements'] as $r) {
			if (!$this->validateFields($this->fields, $r)) {
				return false;
			}
		}
	
		return true;
	}	
	
}