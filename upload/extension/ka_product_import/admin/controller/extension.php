<?php
/*
	$Project: CSV Product Import $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 6.0.0.2 $ ($Revision: 572 $)
*/

namespace extension\ka_product_import;

class ControllerExtension extends \extension\ka_extensions\ControllerSettings {

	protected $ext_code = 'ka_product_import';

	private $extension_pages = array(
		'extension',
		'import_group',
		'import',
		'import_price_rule',
		'import_skip_rule',
		'replacements',
	);

	protected $simple_option_fields = array('price', 'quantity', 'value');

	/*
		These are pages withing the extension route, i.e.:
		full route - extension/ka_product_import/import_price_rule
		page route - import_price_rule
		
		It has to include the extension setting page as well.
	*/
	protected function getExtensionPages() {
		return $this->extension_pages;
	}
	
	protected function onLoad() {
	
		$this->load->language('extension/ka_product_import/common');
		$this->load->language('extension/ka_product_import/settings');
	
 		$this->tables = array(
 		
 			// product table
 			//
 			'product' => array(
 				'fields' => array(
 					'skip_import' => array(
 						'type' => 'tinyint(1)',
 						'query' => "ALTER TABLE `" . DB_PREFIX . "product` ADD `skip_import` TINYINT(1) NOT NULL DEFAULT '0'"
 					),
 				),
 				'indexes' => array(
 					'model' => array(
 						'query' => "ALTER TABLE " . DB_PREFIX . "product ADD INDEX (`model`)",
 					),
 					'sku' => array(
 						'query' => "ALTER TABLE " . DB_PREFIX . "product ADD INDEX (`sku`)",
 					),
 					'upc' => array(
 						'query' => "ALTER TABLE " . DB_PREFIX . "product ADD INDEX (`upc`)",
 					),
 					'ean' => array(
 						'query' => "ALTER TABLE " . DB_PREFIX . "product ADD INDEX (`ean`)",
 					),
 					'master_id' => array(
 						'query' => "ALTER TABLE " . DB_PREFIX . "product ADD INDEX (`master_id`)",
 					),
 				)
 			),

 			// product import table
 			//
 			'ka_product_import' => array(
 				'is_new' => true,
 				'fields' => array(
 					'product_id' => array(
 						'type' => 'int(11)',
 					),
 					'token' => array(
 						'type' => 'varchar(255)',
 					),
 					'is_new' => array(
 						'type' => 'tinyint(1)',
 						'query' => "ALTER TABLE `" . DB_PREFIX . "ka_product_import` ADD `is_new` TINYINT(1) NOT NULL DEFAULT '0'"
 					),
 					'added_at' => array(
 						'type' => 'timestamp'
 					), 					
 				),
 				"query" => "
					CREATE TABLE `" . DB_PREFIX . "ka_product_import` (
						`product_id` int(11) NOT NULL,
						`token` varchar(255) NOT NULL,
						`is_new` tinyint(1) NOT NULL default 0,
						`added_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
						PRIMARY KEY  (`product_id`,`token`)
					) DEFAULT CHARSET=utf8
				", 				
 			),
 			
 			// import profiles table
 			//
 			'ka_import_profiles' => array(
 				'is_new' => true,
 				'fields' => array(
  					'import_profile_id' => array(
  						'type' => 'int(11)',
  					),
  					'name' => array(
  						'type' => 'varchar(128)',
  					),
  					'params' => array(
  						'type' => 'mediumtext',
  					),
  				),
				'indexes' => array(
					'PRIMARY' => array(
						'query' => "ALTER TABLE `" . DB_PREFIX . "ka_import_profiles` ADD PRIMARY KEY (`import_profile_id`)",
					),
					'name' => array(
						'query' => "ALTER TABLE `" . DB_PREFIX . "ka_import_profiles` ADD INDEX (`name`)",
					),
				),
				'query' => "
					CREATE TABLE `" . DB_PREFIX . "ka_import_profiles` (
					  `import_profile_id` int(11) NOT NULL auto_increment,
					  `name` varchar(128) NOT NULL,
					  `params` mediumtext NOT NULL,
					  PRIMARY KEY  (`import_profile_id`),
					  KEY `name` (`name`) 
					) DEFAULT CHARSET=utf8
				",				
			),
 			
 			// import records table
 			//
 			'ka_import_records' => array(
 				'is_new' => true,
 				'fields' => array(
 					'product_id' => array(
 						'type' => 'int(11)',
 					),
 					'record_type' => array(
 						'type' => 'int(6)',
 					),
 					'record_id' => array(
 						'type' => 'int(11)',
 					),
 					'token' => array(
 						'type' => 'varchar(255)',
 					),
 					'added_at' => array(
 						'type' => 'timestamp',
 						'query' => "ALTER TABLE `" . DB_PREFIX . "ka_import_records` ADD added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
 					)
 				),
 				'indexes' => array(
 					'product_id' => array(),
 					'trr' => array(),
 				),
 				'query' => "
					CREATE TABLE `" . DB_PREFIX . "ka_import_records` (  
						`product_id` int(11) NOT NULL,  
						`record_type` int(6) NOT NULL,  
						`record_id` int(11) NOT NULL,  
						`token` varchar(255) NOT NULL, 
						`added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
						KEY `product_id` (`product_id`),  
						KEY `trr` (`token`,`record_type`,`record_id`)
					) DEFAULT CHARSET=utf8
 				",
 			),		
 			
 			// import replacements table
 			//
 			'ka_import_replacements' => array(
 				'is_new' => true,
 				'fields' => array(
  					'import_replacement_id' => array(
  						'type' => 'int(11)',
  					),
  					'import_group_id' => array(
  						'type' => 'int(11)',
  						'query' => "ALTER TABLE `" . DB_PREFIX . "ka_import_replacements` ADD `import_group_id` INT(11) NOT NULL AFTER `new_value`",
  					),
  					'column_name' => array(
  						'type' => 'varchar(255)',
  					),
  					'old_value' => array(
  						'type' => 'varchar(255)',
  					),
  					'new_value' => array(
  						'type' => 'varchar(255)',
  					),
  				),
  				'indexes' => array(
  					'import_group_id' => array(
  						'query' => "ALTER TABLE `" . DB_PREFIX . "ka_import_replacements` ADD INDEX (`import_group_id`)"
  					),
  				),
  				'query' => "
					CREATE TABLE `" . DB_PREFIX . "ka_import_replacements` (  
						`import_replacement_id` int(11) NOT NULL AUTO_INCREMENT,  
						`import_group_id` int(11) NOT NULL,
						`column_name` varchar(255) NOT NULL,
						`old_value` varchar(255) NOT NULL,
						`new_value` varchar(255) NOT NULL,
						PRIMARY KEY (`import_replacement_id`),
						KEY `column_name` (`column_name`),
						KEY `import_group_id` (`import_group_id`)
					) DEFAULT CHARSET=utf8
  				",
			),
			
 			'ka_import_skip_rules' => array(
 				'is_new' => true,
 				'fields' => array(
  					'import_skip_rule_id' => array(
  						'type' => 'int(11)',
  					),
  					'column_name' => array(
  						'type' => 'varchar(255)',
  					),
  					'pattern' => array(
  						'type' => 'varchar(255)',
  					),
  					'rule_action' => array(
  						'type' => 'char(1)',
  					),
  					'sort_order' => array(
  						'type' => 'int(11)',
  					),
  					'import_group_id' => array(
  						'type' => 'int(11)',
  					),
  				),
  				'query' => "
					CREATE TABLE `" . DB_PREFIX . "ka_import_groups` (
					 `import_group_id` int(11) NOT NULL AUTO_INCREMENT,
					 `name` varchar(128) NOT NULL,
					 PRIMARY KEY (`import_group_id`)
					) DEFAULT CHARSET=utf8
				",  				
			),			

 			'ka_import_price_rules' => array(
 				'is_new' => true,
 				'fields' => array(
  					'import_price_rule_id' => array(
  						'type' => 'int(11)',
  					),
  					'column_name' => array(
  						'type' => 'varchar(255)',
  					),
  					'pattern' => array(
  						'type' => 'varchar(255)',
  					),
  					'price_multiplier' => array(
  						'type' => 'decimal(12,4)',
  					),
  					'sort_order' => array(
  						'type' => 'int(11)',
  					),
  					'import_group_id' => array(
  						'type' => 'int(11)',
  					),
  				),
  				'query' => "
					CREATE TABLE `" . DB_PREFIX . "ka_import_skip_rules` (
					 `import_skip_rule_id` int(11) NOT NULL AUTO_INCREMENT,
					 `column_name` varchar(255) NOT NULL,
					 `pattern` varchar(255) NOT NULL,
					 `rule_action` char(1) NOT NULL,
					 `sort_order` int(11) NOT NULL,
					 `import_group_id` int(11) NOT NULL,
					 PRIMARY KEY (`import_skip_rule_id`),
					 KEY `sort_order` (`sort_order`),
					 KEY `import_group_id` (`import_group_id`)
					) DEFAULT CHARSET=utf8
				",  				
			),
			
 			'ka_import_groups' => array(
 				'is_new' => true,
 				'fields' => array(
  					'import_group_id' => array(
  						'type' => 'int(11)',
  					),
  					'name' => array(
  						'type' => 'varchar(128)',
  					),
  				),
  				'query' => "
					CREATE TABLE `" . DB_PREFIX . "ka_import_price_rules` (
					 `import_price_rule_id` int(11) NOT NULL AUTO_INCREMENT,
					 `column_name` varchar(255) NOT NULL,
					 `pattern` varchar(255) NOT NULL,
					 `price_multiplier` decimal(12,4) NOT NULL,
					 `sort_order` int(11) NOT NULL,
					 `import_group_id` int(11) NOT NULL,
					 PRIMARY KEY (`import_price_rule_id`),
					 KEY `sort_order` (`sort_order`),
					 KEY `import_group_id` (`import_group_id`)
					) DEFAULT CHARSET=utf8
				",  				
			),
		);
		
		parent::onLoad();
	}
	
	
	public function getFields() {

		$fields = array(
		
//
// tab: general
//		
			'ka_product_import_enable_product_id' => array(
				'tab' => 'general',
				'code' => 'ka_product_import_enable_product_id',
				'default_value' => 0,
				'type' => 'checkbox',
			),			
			'ka_product_import_status_for_new_products' => array(
				'tab' => 'general',
				'code' => 'ka_product_import_status_for_new_products',
				'default_value' => 'enabled_gt_0',
				'type' => 'select',
				'options' => array(
					'enabled_gt_0' => $this->language->get('Enabled for products with...'),
					'enabled'      => $this->language->get('-Enabled- for all'),
					'disabled'     => $this->language->get('-Disabled- for all'),
				),
			),			
			'ka_product_import_status_for_existing_products' => array(
				'tab' => 'general',
				'code' => 'ka_product_import_status_for_existing_products',
				'default_value' => 'not_change',
				'type' => 'select',
				'options' => array(
					'not_change'   => $this->language->get('Do not change status'),
					'enabled_gt_0' => $this->language->get('Enabled for products with...'),
					'enabled'      => $this->language->get('-Enabled- for all'),
					'disabled'     => $this->language->get('-Disabled- for all'),
				),
			),
			'ka_product_import_key_fields' => array(
				'tab' => 'general',
				'code'     => 'ka_product_import_key_fields',
				'required' => true,
				'default_value' => array('model'),
				'type'     => 'checkboxes',
				'options' => array(
					'model' => $this->language->get('model'),
					'sku'   => $this->language->get('SKU'),
					'upc'   => $this->language->get('UPC'),
					'ean'   => $this->language->get('EAN'),
				),
			),
			
			'ka_product_import_default_out_of_stock_status_id' => array(
				'tab' => 'general',
				'code'     => 'ka_product_import_default_out_of_stock_status_id',
				'required' => true,
				'type'     => 'select',
			),
			

//
// tab: separators
//			
			'ka_product_import_general_separator' => array(
				'tab' => 'separators',
				'code' => 'ka_product_import_general_separator',
				'default_value' => ':::',
				'type' => 'text',
			),
			'ka_product_import_multicat_separator' => array(
				'tab' => 'separators',
				'code' => 'ka_product_import_multicat_separator',
				'default_value' => ':::',
				'type' => 'text',
			),
			'ka_product_import_related_products_separator' => array(
				'tab' => 'separators',
				'code' => 'ka_product_import_related_products_separator',
				'default_value' => ':::',
				'type' => 'text',
			),
			'ka_product_import_image_separator' => array(
				'tab' => 'separators',
				'code' => 'ka_product_import_image_separator',
				'default_value' => ':::',
				'type' => 'text',
			),
			'ka_product_import_options_separator' => array(
				'tab' => 'separators',
				'code' => 'ka_product_import_options_separator',
				'default_value' => ':::',
				'type' => 'text',
			),
			'ka_product_import_parse_simple_option_value' => array(
				'tab' => 'separators',
				'code' => 'ka_product_import_parse_simple_option_value',
				'default_value' => 0,
				'type' => 'checkbox',
			),
			'ka_product_import_simple_option_separator' => array(
				'tab' => 'separators',
				'code' => 'ka_product_import_simple_option_separator',
				'default_value' => '',
				'type' => 'text',
			),
			'ka_product_import_simple_option_field_order' => array(
				'tab' => 'separators',
				'code' => 'ka_product_import_simple_option_field_order',
				'default_value' => implode(";", $this->simple_option_fields),
				'tooltip' => str_replace('%simple_option_fields%', implode(', ', $this->simple_option_fields), $this->language->get('txt_tooltip_ka_product_import_simple_option_field_order')),
				'type' => 'text',
			),		
			
//
// tab optimization
//			
			'ka_product_import_update_interval' => array(
				'tab' => 'optimization',
				'code' => 'ka_product_import_update_interval',
				'default_value' => 15,
				'max_value' => 25,
				'min_value' => 5,
				'type' => 'number',
			),
			'ka_product_import_skip_img_download' => array(
				'tab' => 'optimization',
				'code' => 'ka_product_import_skip_img_download',
				'default_value' => 1,
				'type' => 'checkbox',
			),			
			'ka_product_import_enable_macfix' => array(
				'tab' => 'optimization',
				'code' => 'ka_product_import_enable_macfix',
				'default_value' => 0,
				'type' => 'checkbox',
			),			
			'ka_product_import_compare_as_is' => array(
				'tab' => 'optimization',
				'code' => 'ka_product_import_compare_as_is',
				'default_value' => 0,
				'type' => 'checkbox',
			),
			'ka_product_import_create_options' => array(
				'tab' => 'optimization',
				'code' => 'ka_product_import_create_options',
				'default_value' => 1,
				'type' => 'checkbox',
			),			
			'ka_product_import_generate_seo_keyword' => array(
				'tab' => 'optimization',
				'code' => 'ka_product_import_generate_seo_keyword',
				'default_value' => 1,
				'type' => 'checkbox',
			),			
			'ka_product_import_save_max_date' => array(
				'tab' => 'optimization',
				'code' => 'ka_product_import_save_max_date',
				'default_value' => 1,
				'type' => 'checkbox',
			),			
		);
		
		$this->load->model('localisation/stock_status');
		$stock_statuses = $this->load->model_localisation_stock_status->getStockStatuses();
		
		$options = array();
		foreach ($stock_statuses as $st) {
			$options[$st['stock_status_id']] = $st['name'];
		}
		$fields['ka_product_import_default_out_of_stock_status_id']['options'] = $options;		
		$fields['ka_product_import_default_out_of_stock_status_id']['default_value'] = $stock_statuses[0]['stock_status_id'];
		
		return $fields;
	}
	

	public function index() {

		$this->disableRender();
		parent::index();
		$this->enableRender();
		
		$this->data['simple_option_fields'] = implode(';', $this->simple_option_fields);
		
		$this->showPage('extension/ka_product_import/settings');
	}

	
	protected function validateField($code, $field, $post) {

		if ($code == 'ka_product_import_simple_option_field_order') {	
			if (!empty($post['ka_product_import_parse_simple_option_value'])) {
				$fields = explode(';', trim($post['ka_product_import_simple_option_field_order']));
				$diff = array_diff($fields, $this->simple_option_fields);
				if (!empty($diff)) {
					$this->errors[$code] = $this->language->get("Simple options can contain only these values:" . implode(", ", $this->simple_option_fields));
					return false;
				}
			}
		}			
		
		return parent::validateField($code, $field, $post);
	}

	protected function getFieldValues($fields) {
		unset($fields['ka_product_import_parse_simple_option_value']);
		return parent::getFieldValues($fields);
	}
	
	protected function getFieldsWithData($fields, $old_data, $new_data = null, $errors = array()) {
	
		if (!empty($new_data)) {
			if (empty($new_data['ka_product_import_parse_simple_option_value'])) {
				$new_data['ka_product_import_simple_option_separator']   = '';
				$new_data['ka_product_import_simple_option_field_order'] = '';
			}
		} else {
			if (!empty($old_data['ka_product_import_simple_option_separator'])) {
				$old_data['ka_product_import_parse_simple_option_value'] = 1;
			}
		}
					
		return parent::getFieldsWithData($fields, $old_data, $new_data, $errors);
	}
}