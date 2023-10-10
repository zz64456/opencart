<?php
/*
	$Project: CSV Product Import $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 6.0.0.2 $ ($Revision: 581 $)
	
*/

namespace extension\ka_product_import;

class ControllerImportSkipRule extends \extension\ka_extensions\ControllerForm { 

	private $kamodel_import_skip_rule;
	private $kamodel_import_group;

	function onLoad() {

		$this->load->language('extension/ka_product_import/import_skip_rule');

		// load a model file
		$this->kamodel_import_group = $this->load->kamodel('extension/ka_product_import/import_group');
		$this->kamodel_import_skip_rule = $this->load->kamodel('extension/ka_product_import/import_skip_rule');
		
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
			'import_skip_rule_id' => array(
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
			'rule_action' => array(
				'type' => 'select',
				'required' => true,
				'options' => array(
					'I' => $this->language->get('Include Line'),
					'E' => $this->language->get('Exclude Line'),
				),
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

	      		$import_skip_rule_id = $this->kamodel_import_skip_rule->saveImportSkipRule($this->request->post);
	      		
		  	
				if (!empty($import_skip_rule_id)) {
					$this->addTopMessage($this->language->get("txt_operation_successful"));
	    	  		$this->response->redirect($this->url->linka('extension/ka_product_import/import_skip_rule', $this->url_params->getUrl()));
	    	  	} else {
		      		$this->addTopMessage($this->language->get('txt_operation_failed'), 'E');
	    	  	}
    	  	}
		}
	
    	$this->form();
  	}

  	  	
  	public function delete() {

    	if (isset($this->request->post['selected']) && $this->validateModify()) {
			foreach ($this->request->post['selected'] as $import_skip_rule_id) {
				$this->kamodel_import_skip_rule->deleteImportSkipRule($import_skip_rule_id);
			}
		}
			
		$this->addTopMessage($this->language->get("txt_operation_successful"));
		$this->response->redirect($this->url->linka('extension/ka_product_import/import_skip_rule', $this->url_params->getUrl()));
  	}

  	protected function getList() {

		$this->data['import_skip_rules'] = array();

		$params = $this->url_params->getParams();
		$params['start'] = ($params['page'] - 1) * $this->config->get('config_pagination_admin');
		$params['limit'] = $this->config->get('config_pagination_admin');

		$import_skip_rules_total = $this->kamodel_import_skip_rule->getImportSkipRulesTotal($params);
		
		$results = $this->kamodel_import_skip_rule->getImportSkipRules($params);
    	foreach ($results as $result) {
			$action = array();
			
			$action[] = array(
				'text' => $this->language->get('text_edit'),
				'href' => $this->url->linka('extension/ka_product_import/import_skip_rule|form', $this->url_params->getUrl(['import_skip_rule_id' => $result['import_skip_rule_id']]))
			);
			$result['action'] = $action;
			
			$this->data['import_skip_rules'][] = $result;
		}

		$this->data['import_groups'] = $this->kamodel_import_group->getImportGroups();
		
		$this->data['import_groups_page'] = $this->url->linka('extension/ka_product_import/import_group');
		$this->data['sort_column_name']   = $this->url->linka('extension/ka_product_import/import_skip_rule', $this->url_params->getUrlSort('column_name'));
		
		$this->data['action_add']    = $this->url->linka('extension/ka_product_import/import_skip_rule|form', $this->url_params->getUrl());
		$this->data['action_delete'] = $this->url->linka('extension/ka_product_import/import_skip_rule|delete', $this->url_params->getUrl());
		
		$this->pagination = new \extension\ka_extensions\Pagination(array(
			'total' => $import_skip_rules_total,
			'page'  => $params['page'],
			'limit' => $this->config->get('config_pagination_admin'),
			'url'   => $this->url->linka('extension/ka_product_import/import_skip_rule', $this->url_params->getUrl(['page' => '{page}']))
		));

		$this->addBreadcrumb($this->language->get('txt_list_page_title'));

		$this->data['params'] = $params;
		
		$this->showPage('extension/ka_product_import/import_skip_rule_list');
  	}


  	public function form() {

		// get an entity array
		//		
		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			$org_import_skip_rule = array();
			if (!empty($this->request->post['import_skip_rule_id'])) {
				$org_import_skip_rule = $this->kamodel_import_skip_rule->getImportSkipRule($this->request->post['import_skip_rule_id']);
			}
		
			$import_skip_rule = $this->request->post;
			
			$fields = $this->getFieldsWithData($this->fields, $import_skip_rule, $this->request->post, $this->errors);
			
		} else {
		
	  		if (empty($this->request->get['filter_import_group_id'])) {
	  			$this->addTopMessage($this->language->get('Please select the import group first'), 'E');
				$this->response->redirect($this->url->linka('extension/ka_product_import/import_skip_rule', $this->url_params->getUrl()));
	  		}
		
			if (!empty($this->request->get['import_skip_rule_id'])) {
				$import_skip_rule = $this->kamodel_import_skip_rule->getImportSkipRule($this->request->get['import_skip_rule_id']);
			} else {			
				$import_skip_rule = array(
					'import_skip_rule_id' => 0,
					'column_name'         => '',
					'pattern'             => '',
					'rule_action'         => '',
					'sort_order'          => '',
				);

				$import_skip_rule['import_group_id'] = $this->request->get['filter_import_group_id'];
			}

			$fields = $this->getFieldsWithData($this->fields, $import_skip_rule);
		}

		$this->data['fields'] = $fields;
		
		// define breadcrumbs
		//
   		$this->addBreadcrumb($this->language->get('txt_list_page_title'), $this->url->linka('extension/ka_product_import/import_skip_rule', $this->url_params->getUrl()));
   		$this->addBreadcrumb($import_skip_rule['column_name']);
		$this->document->setTitle($this->language->get('txt_form_page_title'));
   				
   		// define action links
   		//
		$this->data['action_save'] = $this->url->linka('extension/ka_product_import/import_skip_rule|save', $this->url_params->getUrl());
		$this->data['action_back'] = $this->url->linka('extension/ka_product_import/import_skip_rule', $this->url_params->getUrl());

		$this->showPage('extension/ka_product_import/import_skip_rule_form');
  	}
}