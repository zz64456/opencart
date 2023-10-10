<?php
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
*/
	
namespace extension\ka_extensions;

trait TraitControllerForm {

	protected $fields;
	protected $errors;
	protected $url_params;

	/*
		This method is called when the field array should be initialized with actual field value.
	
		$k - field code
		$f - field metadata array
		$old_data - old data information (data requested from db for example)
		$new_data - new data information (post array or null).
	*/
	protected function getFieldWithData($k, $f, $old_data, $new_data) {

		if (!empty($f['code'])) {
			$k = $f['code'];
		} else {
			$f['code'] = $k;
		}		

		if (is_array($new_data)) {
			if (isset($new_data[$k])) {
				$f['value'] = $new_data[$k];
			} else {
				$f['value'] = '';
			}
		} elseif (isset($old_data[$k])) {
			$f['value'] = $old_data[$k];
		} elseif (!empty($f['default_value'])) {
			$f['value'] = $f['default_value'];
		} else {
			$f['value'] = '';
		}
		
		if (!empty($f['value']) && !empty($f['format'])) {
			if ($f['format'] == 'price') {
				$f['value'] = $this->currency->format($f['value'], $this->config->get('config_currency'));
				
			}
		}

		if (!empty($f['type'])) {
			if ($f['type'] == 'image') {
				if (is_file(DIR_IMAGE . $f['value'])) {
					$f['value'] = $f['value'];
					$f['thumb'] = $this->model_tool_image->resize($f['value'], 200, 200);
				} else {
					$f['value'] = '';
					$f['thumb'] = $this->model_tool_image->resize('no_image.png', 200, 200);
				}
				
				$f['default_thumb'] = $this->model_tool_image->resize('no_image.png', 200, 200);
				$f['default_value'] = 'no_image.png';
			}				
		}
		
		return $f;
	}	
	

	/*
		Returns fields filled in with data from old and new arrays
		
		$new_data is not null when the form was submitted with new values.
	*/
	protected function getFieldsWithData($fields, $old_data, $new_data = null, $errors = array()) {
	
		foreach ($fields as $k => $f) {

			if (!empty($errors[$k])) {
				$f['error'] = $errors[$k];
			}
		
			$f = $this->getFieldWithData($k, $f, $old_data, $new_data);

			$fields[$k] = $f;
		}
		
		return $fields;
	}

	
	/*
		Returns a simple array of code->value pairs from the $fields array.
	*/
	protected function getFieldValues($fields) {
	
		$values = array();
	
		foreach ($fields as $k=>$f) {
			$values[$f['code']] = $f['value'];
		}
		
		return $values;
	}
	

	protected function validateField($code, $field, $post) {
	
		if (!empty($field['required'])) {
			if (empty($post[$code])) {
				$this->errors[$code] = sprintf($this->language->get('Field "%s" cannot be empty'), $code);
				return false;
			}
		}

		if (!empty($field['type'])) {

			if (in_array($field['type'], array('number', 'text', 'textarea'))) {
				if (!isset($post[$code])) {
					$this->errors[$code] = sprintf($this->language->get('The field %s was not found'), $this->language->get('txt_title_' . $code));
					return false;
				}
			}
		
			if ($field['type'] == 'number') {
				if (isset($field['min_value'])) {
					if ($field['min_value'] > $post[$code]) {
						$this->errors[$code] = sprintf($this->language->get("Minimum value is %s"), $field['min_value']);
					}
				} 
				if (isset($field['max_value'])) {
					if ($field['max_value'] < $post[$code]) {
						$this->errors[$code] = sprintf($this->language->get("Maximum value is %s"), $field['max_value']);
					}
				}
			}
		}
		
		return true;
	}
	

	protected function validateFields($fields, $post) {

		foreach ($fields as $k => $f) {
			$this->validateField($k, $f, $post);
		}
		
		if (empty($this->errors)) {
			return true;
		}

		return false;
	}	
}