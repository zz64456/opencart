<?php
/*
	$Project: CSV Product Import $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 6.0.0.2 $ ($Revision: 572 $)
*/

namespace extension\ka_product_import\import;

use \extension\ka_product_import\ModelImport;

class ModelOptions extends \extension\ka_extensions\Model {

	protected $extended_types          = array('select', 'radio', 'checkbox');
	protected $options_with_def_values = array('text', 'textarea', 'date', 'time', 'datetime');
	protected $option_types            = array('select', 'radio', 'checkbox', 'text', 'textarea',
	                                     'file', 'date', 'time', 'datetime');
	protected $options_with_images     = array('select', 'radio', 'checkbox');

	protected $import;

	public function setImport($import) {
		$this->import = $import;
	}
	
	protected function fetchOption($option) {
		$is_new = false;
	
		// validate parameters
		//
		$option['value'] = trim($option['value']);

		// STAGE 1: find the option in the store
		//
		$option_where = '';
		if (!empty($option['type'])) {
			$option['type'] = strtolower($option['type']);
		
			if (!in_array($option['type'], $this->option_types)) {
				$this->import->addImportMessage("Invalid option type - $option[type]");
				return false;
			}
		
			$option_where .= " AND o.type = '" . $this->db->escape($option['type']) . "'";
		}

		$qry = $this->db->query("SELECT o.* FROM `" . DB_PREFIX ."option` o
			INNER JOIN " . DB_PREFIX . "option_description od ON o.option_id = od.option_id
			WHERE
				od.name = '" . $this->db->escape($option['name']) . "'
				$option_where
		");

		if (empty($qry->row)) {

			// if the option is NOT found
			//
			if (empty($this->import->params['opt_create_options'])) {
				$this->import->addImportMessage("Option '$option[name]' does not exist in the store. If you want to create options from the file then 
					enable the appropriate setting on the extension settings page."
				);
				return false;
			}

			if (empty($option['type'])) {
				$option['type'] = 'select';
			}
			
			$rec = array(
				'type'   => $option['type'],
			);
			
			if (!empty($option['group_sort_order'])) {
				$rec['sort_order'] = $option['group_sort_order'];
			};

			$option_id = $this->kadb->queryInsert('option', $rec);
			if (empty($option_id)) {
				$this->import->addImportMessage("Cannot create a new option");
				return false;
			}
			
			$option['option_id'] = $option_id;
			
			$is_new = true;

			foreach ($this->import->languages as $lang) {
				$rec = array(
					'option_id'   => $option_id,
					'language_id' => $lang['language_id'],
					'name'        => $option['name']
				);
				$this->kadb->queryInsert('option_description', $rec);
			}

			$this->import->addImportMessage("New option created - $option[name]", 'I');

			// repeat the option request
			//
			$qry = $this->db->query("SELECT o.option_id FROM `" . DB_PREFIX ."option` o
				INNER JOIN " . DB_PREFIX . "option_description od ON o.option_id = od.option_id
				WHERE
					od.name = '" . $this->db->escape($option['name']) . "'
					$option_where
			");

		} else {
		
			$option_id = $qry->row['option_id'];
		
			// update group sort order for existing option group
			//
			if (!empty($option['group_sort_order'])) {
				$rec = array(
					'sort_order' => $option['group_sort_order'],
				);
				$this->kadb->queryUpdate('option', $rec, "option_id = '" . $option_id . "'");
			}
			
			$option = array_merge($option, $qry->row);
		}
		
		return $option;
	}
	
	
	protected function fetchProductOptionId($product_id, $option) {
	
		// find product option id or insert a new one
		//
	   	$qry = $this->db->query("SELECT product_option_id FROM " . DB_PREFIX . "product_option WHERE
	 		product_id='$product_id' AND option_id='$option[option_id]'"
	 	);

		$rec = array();
		
		if (isset($option['required'])) {
			if (in_array(strtolower($option['required']), $this->import::ANSWER_POSITIVE)) {
				$rec['required'] = 1;
			} else if (in_array(strtolower($option['required']), $this->import::ANSWER_NEGATIVE)) {
				$rec['required'] = 0;
			}
		}
		
		if ($this->options_with_def_values) {
			$rec['value'] = $option['value'];
		}
   		
	   	if (empty($qry->row['product_option_id'])) {

			$rec['product_id'] = $product_id;
			$rec['option_id']  = $option['option_id'];
			
			$product_option_id = $this->kadb->queryInsert('product_option', $rec);
		} else {
			$product_option_id = $qry->row['product_option_id'];
			if (!empty($rec)) {
				$this->kadb->queryUpdate('product_option', $rec, "product_option_id = '$product_option_id'");
			}
		}
		
		return $product_option_id;
	}
	
	
	protected function fetchOptionValueId($option) {

		// find option value or insert a new one
		//
		$org_option = $this->db->query("SELECT * FROM " . DB_PREFIX . "option_value_description ovd
				INNER JOIN `" . DB_PREFIX . "option` o ON o.option_id = ovd.option_id
			WHERE ovd.option_id = '" . $option['option_id'] . "'
			AND ovd.name = '" . $this->db->escape($option['value']) . "'")->row;
		
		if (empty($org_option)) {
			$rec = array(
				'option_id' => $option['option_id'],					
			);
			
			$option_value_id = $this->kadb->queryInsert('option_value', $rec);

			foreach ($this->import->languages as $lang) {
				$rec = array(
					'option_id'       => $option['option_id'],
					'option_value_id' => $option_value_id,
					'language_id'     => $lang['language_id'],
					'name'            => $option['value']
				);

				$this->kadb->queryInsert('option_value_description', $rec);
			}

		} else {
			$option_value_id = $org_option['option_value_id'];
			if (empty($option['type'])) {
				$option['type'] = $org_option['type'];
			}
		}

		//
		// collect in $rec array extra option parameters and update the option if required
		//
		$rec = array();
		
		if (in_array($option['type'], $this->options_with_images) && !empty($option['image'])) {
			$file = $this->import->getImageFile($option['image']);
			if ($file === false) {
				$this->import->addImportMessage("Option image '$option[image]' cannot be saved - " . $this->import->lastError);
			} else {
				$rec['image'] = $file;
			}
		}
		
		if (isset($option['sort_order'])) {
			$rec['sort_order'] = $option['sort_order'];
		}
		
		if (!empty($rec)) {
			$this->kadb->queryUpdate('option_value', $rec, "option_value_id = '$option_value_id'");
		}
		
		return $option_value_id;
	}	
	
	/*
		Function saves one product option.

		PARAMETERS:
			...
			option['value'] - it can be empty for text options (and maybe other option types)
			...
			
		RETURNS:
			true  - success
			false - error / fail
	*/
	protected function saveOption($product, $data, &$updated) {

		$option = $this->fetchOption($data);
		if (empty($option)) {
			return false;
		}
		
		if (empty($product['master_id'])) {
			$product_id = $product['product_id'];
		} else {
			$product_id = $product['master_id'];
		}

		$option = array_merge($data, $option);
		
		//
		// STAGE 2: option found/created and we are going to assing it to a product
		//
		$product_option_id = $this->fetchProductOptionId($product_id, $option);
		if (empty($product['master_id'])) {
			$this->import->registerRecord($product_id, ModelImport::RECORD_TYPE_PRODUCT_OPTION_ID, $product_option_id);
		}

		
		/*
			There are two option types in Opencart:
				simple   - user enters a custom value manually
				extended - options with predefined values
		*/
		if (!in_array($option['type'], $this->extended_types)) {
			if (!empty($option['value'])) {
				$updated['fields']['variant'][$product_option_id][] = $option['value'];
			} else {
				$updated['fields']['variant'][$product_option_id] = '';
			}
		}
		
		$option_value_id = $this->fetchOptionValueId($option);
		
		$rec = array(
			'product_option_id' => $product_option_id,
			'product_id'        => $product_id,
			'option_id'         => $option['option_id'],
			'option_value_id'   => $option_value_id
		);
		
		if (isset($option['quantity'])) {
			$rec['quantity'] = $option['quantity'];
		}
		
		if (isset($option['subtract'])) {
			$rec['subtract'] = $option['subtract'];
		}
		
		
		if (isset($option['price'])) {
			$price = (float)$this->import->kaformat->parsePrice($option['price']);
			if ($price < 0) {
				$rec['price'] = abs($price);
				$rec['price_prefix'] = '-';
			} else {
				$rec['price'] = $price;
				$rec['price_prefix'] = '+';
			}
		}

		if (isset($option['points'])) {
			$rec['points']        = abs((float)$option['points']);
			$rec['points_prefix'] = ($option['points'] < 0 ? '-':'+');				
		}

		if (isset($option['weight'])) {
			$rec['weight']        = abs((float)$option['weight']);
			$rec['weight_prefix'] = ($option['weight'] < 0 ? '-':'+');
		}
		
		$qry = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value WHERE
	 		product_option_id = '$product_option_id'
     		AND option_value_id = '$option_value_id' 
     	");
		
     	if (!empty($qry->rows)) {
     	
     		$product_option_value_id = (int)$qry->row['product_option_value_id'];
     	
     		if ($qry->num_rows > 1) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE
					product_option_id = '$product_option_id'
					AND option_value_id = '$option_value_id'
					AND product_option_value_id <> '" . $product_option_value_id  . "'
				");
			}
     	
     		$this->kadb->queryUpdate('product_option_value', $rec, "product_option_value_id = '" . (int) $qry->row['product_option_value_id'] . "'");
		} else {
			$product_option_value_id = $this->kadb->queryInsert('product_option_value', $rec);
		}

		if ($option['type'] == 'checkbox') {
			if (empty($updated['fields']['variant'][$product_option_id])) {
				$updated['fields']['variant'][$product_option_id] = array();
			}
			$updated['fields']['variant'][$product_option_id][] = $product_option_value_id;
		} else {
			$updated['fields']['variant'][$product_option_id] = $product_option_value_id;
		}
		
		$this->import->registerRecord($product['product_id'], ModelImport::RECORD_TYPE_PRODUCT_OPTION_VALUE_ID, $product_option_value_id);

		return true;		
	}
	
	public function saveOptions($row, $data, $product, $flags, &$updated) {

		if (!empty($flags['delete_old'])) {
			if (!empty($this->import->params['matches']['options']) || !empty($this->import->params['matches']['ext_options'])) {
				$this->db->query("DELETE po FROM " . DB_PREFIX . "product_option po
					INNER JOIN `" . DB_PREFIX . "option` o ON po.option_id = o.option_id
					WHERE product_id = '" . $product['product_id']. "'
				");
				
				$this->db->query("DELETE pov FROM " . DB_PREFIX . "product_option_value pov
					INNER JOIN `" . DB_PREFIX . "option` o ON pov.option_id = o.option_id
					WHERE product_id = '" . $product['product_id']. "'
				");
				
				$updated['fields']['variant'] = array();
			}
		}
	
		// STAGE 1: process simple options from the selected columns
		//
		if (!empty($this->import->params['matches']['options'])) {
		
			foreach ($this->import->params['matches']['options'] as $ok => $ov) {
			
				// collect all option data for the option from columns
				//
				$fields = array();
				foreach ($ov['fields'] as $fk => $fv) {
					if (!isset($fv['column']))
						continue;

					$val = trim($row[$fv['column']]);
					if (strlen($val) == 0) {
						continue;
					}
					
					$fields[$fk] = $val;
				}
				
				if (empty($fields['value'])) {
					continue;
				}
				
				// parse the option value and save the option
				//
				if (!empty($this->import->params['cfg_options_separator'])) {
					$option_values = explode($this->import->params['cfg_options_separator'], $fields['value']);
				} else {
					$option_values = array($fields['value']);
				}
				
				foreach ($option_values as $ovalue) {

					if (empty($ovalue)) {
						continue;
					}
					
 					$option = array(
 						'name'     => $ov['name'],
 						'type'     => $ov['type'],
 					);
					
 					
					if (strlen($this->import->params['cfg_simple_option_separator'])) {
						$parsed_values = str_getcsv($ovalue, $this->import->params['cfg_simple_option_separator']);

						/*
							<column name> => position
							Example:
							parced_option_fields = array(							
								'price' => 1,
								'value' => 3
							);
						*/
						foreach ($this->import->params['parced_option_fields'] as $pfk => $pfv) {
							if (isset($parsed_values[$pfv])) {
								$option[$pfk] = $parsed_values[$pfv];
							}
						}
						
					} else {
						$option['value'] = $ovalue;
					}
					
					if (empty($option['value'])) {
						continue;
					}
					
 					if (!empty($ov['required'])) {
 						$option['required'] = $ov['required'];
 					}
 					
 					$option = array_merge($fields, $option);
 					
					$this->saveOption($product, $option, $updated);
				}
			}
		}
		
		// STAGE 2: process extended options
		//
		if (!empty($this->import->params['matches']['ext_options'])) {
			$option = array();
			foreach ($this->import->params['matches']['ext_options'] as $ck => $cv) {
				if (!isset($cv['column']))
					continue;

				$val = $row[$cv['column']];
				$option[$cv['field']] = trim($val);
			}
			
			if (!empty($option['name'])) {
			
				if (!empty($this->import->params['cfg_options_separator'])) {
				
					$multi_options = array();
					$option_keys = array('value', 'quantity', 'subtract', 'image', 'price', 'points', 'weight', 'sort_order');

					$max_option_length = 0;
					foreach ($option_keys as $key) {
						if (isset($option[$key])) {
							$multi_options[$key] = explode($this->import->params['cfg_options_separator'], $option[$key]);
							$max_option_length = max($max_option_length, count($multi_options[$key]));
						}
					}
					
					for ($i = 0; $i < $max_option_length; $i++) {
						$tmp_option = $option;
						
						foreach ($multi_options as $key => $val) {
							if (isset($multi_options[$key][$i])) {
								$tmp_option[$key] = $multi_options[$key][$i];
							} else {
								$tmp_option[$key] = $multi_options[$key][count($multi_options[$key]) - 1];
							}
						}
						
						$this->saveOption($product, $tmp_option, $updated);
					}
					
				} else {
					$this->saveOption($product, $option, $updated);
				}
			}
		}
	}
	
	
	public function deleteRecords() {

		if ($this->import->params['update_mode'] == 'replace') {
			if (!empty($this->import->params['matches']['ext_options'])
				|| !empty($this->import->params['matches']['options']))
			{
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_option 
					WHERE product_id IN (SELECT product_id FROM " . DB_PREFIX . "ka_product_import
							WHERE token = '" . $this->session->data['ka_token'] . "'
							AND is_new = 0
					) 
					AND	product_option_id NOT IN (SELECT record_id FROM " . DB_PREFIX . "ka_import_records
						WHERE record_type = '" . ModelImport::RECORD_TYPE_PRODUCT_OPTION_ID . "'
						AND token = '" . $this->session->data['ka_token'] . "'
					)
				");
				
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value 
					WHERE product_id IN (SELECT product_id FROM " . DB_PREFIX . "ka_product_import 
							WHERE token = '" . $this->session->data['ka_token'] . "'
							AND is_new = 0
					) 
					AND product_option_value_id NOT IN (SELECT record_id FROM " . DB_PREFIX . "ka_import_records
						WHERE record_type = '" . ModelImport::RECORD_TYPE_PRODUCT_OPTION_VALUE_ID . "'
						AND token = '" . $this->session->data['ka_token'] . "'
					)
				");
			}
		}
	}
	
}