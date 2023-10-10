<?php
/*
	$Project: CSV Product Import $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 6.0.0.2 $ ($Revision: 581 $)
	
*/

namespace extension\ka_product_import;

class ControllerImportGroup extends \extension\ka_extensions\ControllerForm { 

	private $kamodel_import_group;

	function onload() {
		// load langauge files
		$this->load->language('extension/ka_product_import/import_group');
		
		// load a model file
		$this->kamodel_import_group = $this->load->kamodel('extension/ka_product_import/import_group', 'kamodel_import_group');
		
		parent::onLoad();
	}
	
  	protected function getPageUrlParams() {
  	
  		$params = array(
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
		
		 	if ($this->validate()) {

	      		$import_group_id = $this->kamodel_import_group->saveImportGroup($this->request->post);
	      		
				if (!empty($import_group_id)) {
					$this->addTopMessage($this->language->get("txt_operation_successful"));
	    	  		$this->response->redirect($this->url->linka('extension/ka_product_import/import_group', $this->url_params->getUrl()));
	    	  	} else {
		      		$this->addTopMessage($this->language->get('txt_operation_failed'), 'E');
	    	  	}
    	  	}
		}
	
    	$this->form();
  	}

  	  	
  	public function delete() {

    	if (isset($this->request->post['selected']) && $this->validateModify()) {
			foreach ($this->request->post['selected'] as $import_group_id) {
				$this->kamodel_import_group->deleteImportGroup($import_group_id);
			}
		}

		$this->addTopMessage($this->language->get("txt_operation_successful"));
		$this->response->redirect($this->url->linka('extension/ka_product_import/import_group', $this->url_params->getUrl(), true));
  	}


  	protected function getList() {

		$this->data['import_groups'] = array();

		$params = $this->url_params->getParams();
		$params['start'] = ($params['page'] - 1) * $this->config->get('config_pagination_admin');
		$params['limit'] = $this->config->get('config_pagination_admin');

		$import_groups_total = $this->kamodel_import_group->getImportGroupsTotal($params);
		
		$results = $this->kamodel_import_group->getImportGroups($params);
 
    	foreach ($results as $result) {
			$action = array();
			
			$action[] = array(
				'text' => $this->language->get('text_edit'),
				'href' => $this->url->linka('extension/ka_product_import/import_group|form', $this->url_params->getUrl(['import_group_id' => $result['import_group_id']]))
			);
			$result['action'] = $action;
			
			$this->data['import_groups'][] = $result;
		}

		$this->pagination = new \extension\ka_extensions\Pagination(array(
			'total' => $import_groups_total,
			'page'  => $params['page'],
			'limit' => $this->config->get('config_pagination_admin'),
			'url'   => $this->url->linka('extension/ka_product_import/import_group', $this->url_params->getUrl(['page' => '{page}']))
		));
		
		$this->data['sort_name'] = $this->url->linka('extension/ka_product_import/import_group', $this->url_params->getUrlSort('name'), true);
		
		// breacrumbs and title
		//
  		$this->document->setTitle($this->language->get('txt_list_page_title'));
   		$this->addBreadcrumb($this->language->get('txt_list_page_title'));
   		
		$this->data['action_add'] = $this->url->linka('extension/ka_product_import/import_group|form', $this->url_params->getUrl());
		$this->data['action_delete'] = $this->url->linka('extension/ka_product_import/import_group|delete', $this->url_params->getUrl());
		
		$this->data['params'] = $params;
		
		$this->showPage('extension/ka_product_import/import_group_list');
  	}
  	

  	public function form() {

		// get an entity array
		//
		if (!empty($this->request->get['import_group_id'])) {
			$import_group = $this->kamodel_import_group->getImportGroup($this->request->get['import_group_id']);
		} else {			
			$import_group = array(
				'name' => $this->language->get('New Group'),
			);
		}
		
		// fields
		//
		$fields = $this->getFieldsWithData($this->fields, $import_group);
		$this->data['fields'] = $fields;

		// breacrumbs and title
		//
    	$this->document->setTitle($this->language->get('txt_form_page_title'));
		
   		$this->addBreadcrumb($this->language->get('txt_list_page_title'), $this->url->linka('extension/ka_product_import/import_group', $this->url_params->getUrl()));
   		$this->addBreadcrumb($import_group['name']);
   		
   		// define action links
   		//
		$this->data['action_save'] = $this->url->linka('extension/ka_product_import/import_group|save', $this->url_params->getUrl(), true);
		$this->data['action_back'] = $this->url->linka('extension/ka_product_import/import_group', $this->url_params->getUrl(), true);

		$this->showPage('extension/ka_product_import/import_group_form');
  	}


	protected function getFields() {
	
		$fields = array(
			'name' => array(
				'code'     => 'name',
				'required' => true,
				'default_value' => '',
				'type'     => 'text',
			),
		);
		
		return $fields;
	}

}