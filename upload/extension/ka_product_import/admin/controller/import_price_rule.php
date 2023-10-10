<?php
/*
	$Project: CSV Product Import $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 6.0.0.2 $ ($Revision: 581 $)
	
*/

namespace extension\ka_product_import;

class ControllerImportPriceRule extends \extension\ka_extensions\ControllerForm { 

	private $kamodel_import_price_rule;
	private $kamodel_import_group;

	function onLoad() {
		// load langauge files
		$this->load->language('extension/ka_product_import/common');
		$this->load->language('extension/ka_product_import/import_price_rule');

		// load a model file
		$this->kamodel_import_price_rule = $this->load->kamodel('extension/ka_product_import/import_price_rule');
		$this->kamodel_import_group = $this->load->kamodel('extension/ka_product_import/import_group');
		
		parent::onLoad();
	}
	
  	protected function getPageUrlParams() {
  	
  		$params = array(
			'sort'  => 'column_name',
			'filter_import_group_id' => 0,
			'order' => 'DESC', 
			'page'  => '1',
		);
		
		return $params;
	}
	

	protected function getFields() {
	
		$fields = array(
			'import_price_rule_id' => array(
				'type' => 'hidden',
			),
			'import_group_id' => array(
				'required' => true,
				'type' => 'hidden',
			),
			'column_name'  => array(
				'required' => true,
			),
			'pattern'     => array(
				'required' => true,
			),
			'price_multiplier' => array(				
			),
			'sort_order'  => array(),
		);
		
		return $fields;
	}

  	public function index() {
    	$this->getList();
  	}
  	
  	
  	public function save() {

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {

		 	if ($this->validate()) {

	      		$import_price_rule_id = $this->kamodel_import_price_rule->saveImportPriceRule($this->request->post);
				
				if (!empty($import_price_rule_id)) {
					$this->addTopMessage($this->language->get("txt_operation_successful"));
	    	  		$this->response->redirect($this->url->linka('extension/ka_product_import/import_price_rule', $this->url_params->getUrl()));
	    	  	} else {
		      		$this->addTopMessage($this->language->get('txt_operation_failed'), 'E');
	    	  	}
    	  	}
		}
	
    	$this->form();
  	}

  	  	
  	public function delete() {

    	if (isset($this->request->post['selected']) && $this->validateModify()) {
			foreach ($this->request->post['selected'] as $import_price_rule_id) {
				$this->kamodel_import_price_rule->deleteImportPriceRule($import_price_rule_id);
			}
		}
			
		$this->addTopMessage($this->language->get("txt_operation_successful"));
		$this->response->redirect($this->url->linka('extension/ka_product_import/import_price_rule', $this->url_params->getUrl()));
  	}


  	protected function getList() {
  	
		$this->data['import_price_rules'] = array();

		$params = $this->url_params->getParams();
		$params['start'] = ($params['page'] - 1) * $this->config->get('config_pagination_admin');
		$params['limit'] = $this->config->get('config_pagination_admin');

		$import_price_rules_total = $this->kamodel_import_price_rule->getImportPriceRulesTotal($params);
		
		$results = $this->kamodel_import_price_rule->getImportPriceRules($params);

    	foreach ($results as $result) {
			$action = array();
			
			$action[] = array(
				'text' => $this->language->get('text_edit'),
				'href' => $this->url->linka('extension/ka_product_import/import_price_rule|save', $this->url_params->getUrl(['import_price_rule_id' => $result['import_price_rule_id']]))
			);
			$result['action'] = $action;
			
			$this->data['import_price_rules'][] = $result;
		}

		$this->data['import_groups'] = $this->kamodel_import_group->getImportGroups();		

		$this->data['import_groups_page'] = $this->url->linka('extension/ka_product_import/import_group');
		$this->data['sort_column_name']   = $this->url->linka('extension/ka_product_import/import_price_rule', $this->url_params->getUrlSort('column_name'));
		
		$this->data['action_add']    = $this->url->linka('extension/ka_product_import/import_price_rule|form', $this->url_params->getUrl());
		$this->data['action_delete'] = $this->url->linka('extension/ka_product_import/import_price_rule|delete', $this->url_params->getUrl());
		
		$this->pagination = new \extension\ka_extensions\Pagination(array(
			'total' => $import_price_rules_total,
			'page'  => $params['page'],
			'ur'    => $this->url->linka('extension/ka_product_import/import_price_rule', $this->url_params->getUrl(['page' => '{page}']))
		));

		$this->addBreadcrumb($this->language->get('txt_list_page_title'));
		
		$this->data['params'] = $params;
		
		$this->showPage('extension/ka_product_import/import_price_rule_list');		
  	}


  	public function form() {

		// get an entity array
		//		
		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			$org_import_price_rule = array();
			if (!empty($this->request->post['import_price_rule_id'])) {
				$org_import_price_rule = $this->kamodel_import_price_rule->getImportPriceRule($this->request->post['import_price_rule_id']);
			}
		
			$import_price_rule = $this->request->post;
			$this->data['errors'] = $this->errors;
			
		} else {
		
	  		if (empty($this->request->get['filter_import_group_id'])) {
	  			$this->addTopMessage($this->language->get('Please select the import group first'), 'E');
				$this->response->redirect($this->url->linka('extension/ka_product_import/import_price_rule', $this->url_params->getUrl()));
	  		}
		
			if (!empty($this->request->get['import_price_rule_id'])) {
				$import_price_rule = $this->kamodel_import_price_rule->getImportPriceRule($this->request->get['import_price_rule_id']);
			} else {			
				$import_price_rule = array(
					'import_price_rule_id' => 0,
					'column_name'         => '',
					'pattern'             => '',
					'price_multiplier'    => '1.0',
					'sort_order'          => '',
					
				);

				$import_price_rule['import_group_id'] = $this->request->get['filter_import_group_id'];				
			}
		}
		
		$fields = $this->getFieldsWithData($this->fields, $import_price_rule);
		$this->data['fields'] = $fields;

		// define breadcrumbs
		//
   		$this->addBreadcrumb($this->language->get('txt_list_page_title'), $this->url->linka('extension/ka_product_import/import_price_rule', $this->url_params->getUrl()));
   		$this->addBreadcrumb($import_price_rule['column_name']);
		$this->document->setTitle($this->language->get('txt_form_page_title'));
   				
   		// define action links
   		//
		$this->data['action_save'] = $this->url->linka('extension/ka_product_import/import_price_rule|save', $this->url_params->getUrl());
		$this->data['action_back'] = $this->url->linka('extension/ka_product_import/import_price_rule', $this->url_params->getUrl());

		$this->showPage('extension/ka_product_import/import_price_rule_form');
  	}
 	
}