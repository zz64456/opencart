<?php
/*
 $Project: CSV Product Import $
 $Author: karapuz team <support@ka-station.com> $
 $Version: 6.0.0.2 $ ($Revision: 573 $) 
*/

namespace extension\ka_product_import;

use \extension\ka_extensions\KaGlobal;
use \extension\ka_extensions\KaUrlify;

class ModelImport extends \extension\ka_extensions\Model {

	// constants
	const RECORD_TYPE_PRODUCT_OPTION_ID       = 1;
	const RECORD_TYPE_PRODUCT_OPTION_VALUE_ID = 2;
	const RECORD_TYPE_FIRST_VARIANT_PROCESSED = 3;

	public $sec_per_cycle    = 10;
	
	protected $enclosure        = '"';
	protected $escape           = "\x00";

	// available delimiters
	protected $delimiters;
	
	// timestamp when the import started (to prevent page time outs)
	protected $started_at;
	
	// available key fields
	public $key_fields;
	
	// kaformat object for parsing prices and other
	public $kaformat;
	
	// file object
	protected $file;

	private $kamodel_import_groups;
	private $kamodel_replacements;
	private $kamodel_skip_rule;
	private $kamodel_price_rule;
	
	// session variables
	//
	// they are public for a quick access from the task
	//
	public $stat;
	public $params;

	// this value will contain the main import model like 'import_product'
	protected $import_model;
	
	protected $org_error_handler = null;

	public $lastError;
	
	// replacements cache
	protected $replacements;
	
	// skip imports
	protected $skip_rules;

	// price rules
	public $price_multiplier;
	protected $price_rules;

	protected $messages;
	protected $entity_mark = ''; // current entity identfier for showing in import messages
	protected $kalog = null;
	
	// these errors will throw an exception instead of showing a warning/notice in the log
	//
	protected $catchable_errors = array(
		'getimagesize(): Read error!',
		'mkdir(): Invalid path',
		'mkdir(): Invalid argument',
		'mkdir(): No such file or directory'
	);

	public $languages;
	
	const ANSWER_POSITIVE = array('y', 'yes','enabled', '1');
	const ANSWER_NEGATIVE = array('n', 'no','disabled', '0');
	
	protected function onLoad() {

		parent::onLoad();

		setlocale(LC_NUMERIC, 'C');

		$this->delimiters = array(
			'tab' => $this->language->get('tab'),
			";"  => $this->language->get('semicolon') . ' ";"',
			','  => $this->language->get('comma') . ' ","',
			'|'  => $this->language->get('pipe') . ' "|"',
			' '  => $this->language->get('space') .' " "',
		);
		
		$this->kaformat = new \extension\ka_extensions\Format();
		
		$this->file = new \extension\ka_extensions\FileUTF8();
		
 		$this->stat   = &$this->getSession('stat');
		$this->params = &$this->getSession('params');
		
 		$this->kalog    = new \Opencart\System\Library\Log('ka_product_import.log');

 		$upd = $this->config->get('ka_product_import_update_interval');
 		if ($upd >= 5 && $upd <= 25) {
 			$this->sec_per_cycle = $upd;
 		}
 		
		$this->load->model('catalog/product');
		
		$this->org_error_handler = set_error_handler(array($this, 'import_error_handler'));	
		
		$this->kacurl = new \extension\ka_extensions\CURL();
		
		$this->kamodel_import_group = $this->load->kamodel('extension/ka_product_import/import_group');
		$this->kamodel_replacements = $this->load->kamodel('extension/ka_product_import/replacements');
		$this->kamodel_skip_rule    = $this->load->kamodel('extension/ka_product_import/import_skip_rule');
		$this->kamodel_price_rule   = $this->load->kamodel('extension/ka_product_import/import_price_rule');

		// load languages for multi-language import
		//
		$this->load->model('localisation/language');
		$this->languages = $this->model_localisation_language->getLanguages();
	}
	
	
	protected function getRealDelimiter($arg_delimiter) {
		$delimiter = str_replace(['tab', '\s'],["\t"," "], $arg_delimiter);	
		return $delimiter;
	}
	

	public function getDefaultImportParams() {
	
		$params = array(
			'update_mode'         => 'add',
			'cat_separator'       => '///',
			'add_to_each_category' => false,
			'location'            => 'local',
			'store_ids'           => array(0),
			'step'                => 1,
			'images_dir'          => '',
			'incoming_images_dir' => 'catalog' . DIRECTORY_SEPARATOR . 'incoming',
			'default_category_id' => 0,
			'parent_category_id'  => 0,
			'charset'             => 'ISO-8859-1',
			'charset_option'      => 'predefined',
			'delimiter'           => ';',
			'delimiter_option'    => 'predefined',
			'profile_id'          => '', // for the first step
			'file_path'           => '',
			'file_name'           => '',
			'rename_file'         => true,
			'price_multiplier'    => '',
			'disable_not_imported_products' => false,
			'skip_new_products'   => false,
			'sr_import_group_id'  => 0, // Skip Rules Import Group Id
			'pr_import_group_id'  => 0, // Price Rules Import Group Id
			'ir_import_group_id'  => 0, // Import Replacements group id
			'import_as_plain_text' => false,
			'download_source_dir' => 'files',
			'file_name_postfix'   => 'generate',
			'tpl_product_id'      => 0,
			'default_values'      => array(),
			'key_field_prefix'    => '',
		);

		return $params;
	}
	
	
	// it is used as a marker to stop any existing import
	//
	public function resetStat() {
		$this->stat = array();
	}
	
	
	public function import_error_handler($errno, $errstr, $errfile, $errline) {

		if (empty($this->stat['status'])) {
			if ($errno == E_WARNING) {
				if (preg_match("/iconv stream filter.*invalid multibyte/", $errstr)) {
					return true;
				}
			}
		}

		foreach ($this->catchable_errors as $ce) {
			if (preg_match("/" . preg_quote($ce) . "/", $errstr, $matches)) {
				throw new \Exception($errstr);
			}
		}
		
		return call_user_func_array($this->org_error_handler, func_get_args());
	}

	public function getSecPerCycle() {
		return $this->sec_per_cycle;
	}
	
	public function setImportModel($import_model) {
		$this->import_model = $import_model;
		$this->key_fields = $this->import_model->getKeyFields();
		
		$this->import_model->setImport($this);
		
		
	}
	
	public function isDBPrepared() {

		$res = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "ka_import_profiles'");
		if (empty($res->rows)) {
			return false;
		}

		$res = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "ka_product_import'");		
		if (empty($res->rows)) {
			return false;
		}

		$res = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "ka_import_groups'");
		if (empty($res->rows)) {
			return false;
		}

		$res = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "ka_import_price_rules'");
		if (empty($res->rows)) {
			return false;
		}
		
		return true;
	}
	

	public function getUploadMaxFilesize() {
		static $max_filesize;

		if (!isset($max_filesize)) {
			$post_max_size       = $this->kaformat->convertToByte(ini_get('post_max_size'));
			$upload_max_filesize = $this->kaformat->convertToByte(ini_get('upload_max_filesize'));
			$max_filesize        = intval(min($post_max_size, $upload_max_filesize));
		}

    	return $max_filesize;
	}

	
	public function getStores() {

		$this->load->model('setting/store');
		$stores = $this->model_setting_store->getStores();

		$stores[] = array(
			'store_id' => 0,
			'name'     => $this->config->get('config_name') . $this->language->get('text_default'),
			'url'      => HTTP_CATALOG,
		);

		return $stores;
	}

	
	public function getCharsets() {
		$arr = array(
			'ISO-8859-1'   => $this->language->get('ISO-8859-1 (Western Europe)'),
			'ISO-8859-5'   => $this->language->get('ISO-8859-5 (Cyrillc, DOS)'),
			'UTF-16'       => $this->language->get('UNICODE (MS Excel text format)'),
			'KOI-8R'       => $this->language->get('KOI-8R (Cyrillic, Unix)'),
			'UTF-7'        => $this->language->get('UTF-7'),
			'UTF-8'        => $this->language->get('UTF-8'),
			'windows-1250' => $this->language->get('windows-1250 Central Europ...'),
			'windows-1251' => $this->language->get('windows-1251 (Cyrillc)'),
			'windows-1252' => $this->language->get('windows-1252 Western langu...'),
			'windows-1253' => $this->language->get('windows-1253 (Greek)'),
			'windows-1254' => $this->language->get('windows-1254 (Turkish)'),
			'windows-1255' => $this->language->get('windows-1255 (Hebrew)'),
			'windows-1256' => $this->language->get('windows-1256 (Arabic)'),
			'windows-1257' => $this->language->get('windows-1257 Baltic langua...'),
			'windows-1258' => $this->language->get('windows-1258 (Vietnamese)'),
			'big5'         => $this->language->get('Chinese Traditional (Big5)'),
			'CP932'        => $this->language->get('CP932 (Japanese)'),
		);

		return $arr;
	}


	public function getDelimiters() {
		return $this->delimiters;
	}

	
	public function examineFileData($file) {
	
		$result = array(
			'error' => '',
			'charset' => '',
			'delimiter' => '',
		);

		if (empty($file)) {
			return $result;
		}
		
		if (ord($file[0]) == 0xd0 && ord($file[1]) == 0xcf) {
			$result['error'] = $this->language->get('The file looks like a nativ...');
			return $result;
		}
		
		if ((ord($file[0]) == 0xff && ord($file[1]) == 0xfe)
		) {
			$result['charset'] = 'UTF-16';
		} elseif (ord($file[0]) == 0xef && ord($file[1]) == 0xbb) {
			$result['charset'] = 'UTF-8';
		}
		
		// try to detect a field delimiter
		//
		$max = array(
			'delimiter' => '',
			'count' => 0,
		);		
		foreach ($this->delimiters as $delimiter => $name) {
		
			$real_delimiter = $this->getRealDelimiter($delimiter);
		
			if (in_array($real_delimiter, array(' '))) {
				continue;
			}
		
			$arr = str_getcsv($file, $real_delimiter);
			
			if (count($arr) > $max['count']) {
				$max['delimiter'] = $delimiter;
				$max['count']     = count($arr);
			}
		}
		
		if ($max['count'] >= 4) {
			$result['delimiter'] = $max['delimiter'];
		}
		
		return $result;
	}


	/*
		TRUE  - success
		FALSE - fail. See lastError for details.
	*/
	public function openFile($params) {

		if (empty($params['file']) || !is_file($params['file'])) {
			$this->lastError = "File '" . $params['file'] ."' does not exist.";
			return false;
		}

		$options = array();

		if (!empty($params['opt_enable_macfix'])) {
			$options['enable_macfix'] = true;
		}
		
		if (!$this->file->fopen($params['file'], 'r', $options, $params['charset'])) {
			$this->lastError = $this->file->getLastError();
			return false;
		}
		
		return true;
	}

	
	public function readColumns($delimiter) {

		if (strlen($delimiter)) {
			$this->lastError = "Delimiter is empty";
			$delimiter = $this->getRealDelimiter($delimiter);
		}
	
		if (empty($this->file->handle)) {
			$this->lastError = "readColumns: file handle is not valid.";
			return false;
		}
	
		$this->file->rewind();
		
		$columns = fgetcsv($this->file->handle, 0, $delimiter, $this->enclosure, $this->escape);
		if (empty($columns)) {
			return false;
		}

		$count = array();
		
		$new_columns = array();
		foreach ($columns as $cv) {
			$cv = trim($cv);
			
			// specify a name for empty columns
			if (empty($cv)) {
				$cv = 'empty';
			}
			
			if (!empty($count[$cv])) {
				$count[$cv]++;
				$cv = $cv . '-' . $count[$cv];
			} else {
				$count[$cv] = 1;
			}
			$new_columns[] = $cv;
		}

		return $new_columns;
	}


	/*
		The function returns 'matches' array with assigned columns and some other parameters.

		POST REQUEST:
			fields[<fieldid>     => <column position in the file>
			discounts[<fieldid>] => <column position in the file>
			...
	*/
	public function getMatchesByPositions($sets, $columns, $post) {

		$matches = array();
		
		foreach ($sets as $sk => $sv) {

			if (empty($sv)) {
				continue;
			}

			if (empty($post[$sk])) {
				continue;
			}
			
			$fields = $post[$sk];

			foreach ($sv as $f_idx => $f_data) {
			
				if ($sk == 'filter_groups') {
					$f_key = $f_data['filter_group_id'];
					
				} elseif ($sk == 'attributes') {
					$f_key = $f_data['attribute_id'];
					
				} elseif ($sk == 'options') {
					$f_key = $f_data['option_id'];
					
				} else {
					$f_key = (isset($f_data['field']) ? $f_data['field'] : $f_idx);
				}
				
				if (isset($fields[$f_key])) {
				
					if (!isset($matches[$sk])) {
						$matches[$sk] = array();
					}
					
					if ($sk == 'options') {
						foreach ($fields[$f_key] as $ffk => $ffv) {
							if ($fields[$f_key][$ffk] > 0) {
								$matches[$sk][$f_key]['fields'][$ffk] = $columns[$fields[$f_key][$ffk]-1];
							} else {
								$matches[$sk][$f_key]['fields'][$ffk] = '';
							}
						}
						continue;
					}
				
					if (is_array($fields[$f_key])) {

						if (empty($f_data)) {
							$fields[$f_key] = array_unique($fields[$f_key]);
						}
						
						$matches[$sk][$f_key] = array();
						
						foreach ($fields[$f_key] as $ffk => $ffv) {
							if ($fields[$f_key][$ffk] > 0) {
								if (!empty($f_data)) {
									$matches[$sk][$f_key][$ffk] = $columns[$fields[$f_key][$ffk]-1];
								} else {
									$matches[$sk][$f_key][] = $columns[$fields[$f_key][$ffk]-1];
								}
							}
						}
						
					} else {
						if ($fields[$f_key] > 0) {
							$matches[$sk][$f_key] = $columns[$fields[$f_key]-1];
						} else {
							$matches[$sk][$f_key] = '';
						}
					}
				}
			}
		}

		$matches['required_options'] = (isset($post['required_options'])) ? $post['required_options'] : array();
		
		$matches['set_default_for'] = (isset($post['set_default_for'])) ? $post['set_default_for'] : array();

		return $matches;
	}


	
	/*
		PARAMETERS:
			$sets - an array with field sets
				array(
					'fields' =>
									array(
										'field' => 'model',
										'required' => true,
										'copy'  => true,
										'name'  => 'Model',
										'descr' => 'A unique product code required by Opencart'
									),
									...
					'options'    =>
					'attributes' =>
					...
			 	
			$columns - an array with column names like
				arrray(
					0 => 'model'
					1 => 'name'
					...
				);

		RETURNS:
			$matches - on success - an array of field matches
			false    - error.
	*/
	public function getMatchesByColumnNames($sets, $columns) {

		$tmp = array();
		
		foreach ($columns as $ck => $cv) {
			$field_key = trim(mb_strtolower($cv, 'utf-8'));
			$tmp[$field_key] = $cv;
		}
		$columns = $tmp;
		
		$matches = array();
		
		/*
			'set name' => (
				<field id>
				<readable name for users>
				<prefix>
			);
		*/
		$prefixes = array(
			'fields'        => array('field', 'name', ''), 
			'attributes'    => array('attribute_id', 'name', 'attribute:'),
			'filter_groups' => array('filter_group_id', 'name', 'filter:'), 
			'options'       => array('option_id', 'name', 'simple option:'),
			'ext_options'   => array('field', 'name', 'option:'),
			'discounts'     => array('field', 'name', 'discount:'),
			'specials'      => array('field', 'name', 'special:'),
			'reward_points' => array('field', 'name', 'reward points:'),
			'subscriptions' => array('field', 'name', 'subscription:'),
			'reviews'       => array('field', 'name', 'review:'),
		);

		foreach ($sets as $sk => $sv) {
		
			if (!empty($prefixes[$sk])) {
				$prefix = $prefixes[$sk];
			} else {
				$prefix = $prefixes['fields'];
			}
		
			if ($sk == 'options') {
			
				foreach ($sv as $ok => $ov) {
				
					foreach ($ov['fields'] as $fk => $fv) {
					
						if (isset($fv['column'])) {
							$matches[$sk][$ov['option_id']]['expanded'] = true;
							continue;
						}
				
						$name = $this->unclean($prefix[2] . trim($ov['name']));

						$possible_names = array();
						if ($fv['field'] == 'value') {
							$possible_names[] = mb_strtolower($name);
							$possible_names[] = trim(mb_strtolower($ov['name']));
						} else {
							$possible_names[] = mb_strtolower($name . ':' . $fv['field']);
							$possible_names[] = mb_strtolower($name . ':' . $fv['name']);
						}

						$column_indexes = $this->findColumnsByNames($columns, $possible_names);
						
						if (!empty($column_indexes)) {
							$matches[$sk][$ov['option_id']]['fields'][$fk] = $column_indexes;
							$matches[$sk][$ov['option_id']]['expanded'] = true;
						}
					}
						
				}
				
			} else {
			
				foreach ($sv as $idx => $field) {

					$code = $field[$prefix[0]];
				
					// mulit-value columns with named columns
					//
					if (!empty($field['values'])) {
					
						$matched_columns = array();
						
						foreach ($field['values'] as $mvk => $mvv) {
						
							$possible_names = array(
								$prefix[2] . mb_strtolower($field[$prefix[0]] . ':' . $mvv['code'], 'utf-8'),
								$prefix[2] . mb_strtolower($field[$prefix[1]] . ':' . $mvv['code'], 'utf-8')
							);
							
							if (!empty($mvv['is_default'])) {
								$possible_names = array_merge($possible_names, 
									array(
										$prefix[2] . mb_strtolower($field[$prefix[0]], 'utf-8'),
										$prefix[2] . mb_strtolower($field[$prefix[1]], 'utf-8')
									)
								);
							}

							$column_index = $this->findColumnsByNames($columns, $possible_names);
							if (!is_null($column_index)) {
								$matches[$sk][$code][$mvv['language_id']] = $column_index;
							}
						}

					// regular and cloned columns (cloned columns use numeric indexes)
					//
					} else {
					
						$possible_names = array(
							$prefix[2] . mb_strtolower($field[$prefix[0]], 'utf-8'),
							$prefix[2] . mb_strtolower($field[$prefix[1]], 'utf-8')
						);
						
						$column_index = $this->findColumnsByNames($columns, $possible_names, !empty($field['can_be_cloned']));
						
						if (!is_null($column_index)) {
							$matches[$sk][$code] = $column_index;
						}
					}
				}
			}
		}

		return $matches;
	}
	
	
	/*
		PARAMETERS:
			columns - list of all columns from the file
			names   - suitable column names
			is_multi_value - indication of a field that may have multiple columns (array can be returned)
			                 like multiple images, language fields
			                 
		RETURNS:
			null    - when no matched column found
			column  - index of matched column
			columns - array of matched columns (when the field may be related to multiple columns)
	*/
	protected function findColumnsByNames($columns, $names, $is_multi_value = false) {

		$column_keys = array();

		foreach ($names as $name) {
		
			if (empty($name)) {
				continue;
			}
			
			$search_name = str_replace(['_', ' '], '', $name);
		
			foreach ($columns as $ck => $cv) {

				if (empty($ck)) {
					continue;
				}

				$col_name = str_replace(['_',' '], '', (string)$ck);

				if ($col_name == $search_name) {
					$column_keys[] = $columns[$ck];	
					
				} elseif ($is_multi_value) {
					$col_name = str_replace(['_', ' '], '', (string)$ck);
					if (stripos($col_name, $search_name) === 0) {
						$column_keys[] = $columns[$ck];
					}
				}
			}
		}

		// nothing is found
		if (empty($column_keys)) {
			return null;
		}
		
		// multi value
		if ($is_multi_value) {
			$column_keys = array_unique($column_keys);
			return $column_keys;
		}

		// single value
		$column_keys = reset($column_keys);
		
		return $column_keys;
	}
	

	/*
		$sets    - sets array
		$matches - matched column names to product fields
		$columns - array of column names (<position> => <name>)
	*/
	public function copyMatchesToSets(&$sets, $matches, $columns) {
	
		// remove empty columns except the first one meaning 'not selected'
		//
		foreach ($columns as $ck => $cv) {
			if (strlen($cv)) {
				$tmp[$cv] = $ck;
			}
		}
		$columns = $tmp;

		foreach ($sets as $sk => $sv) {
		
			foreach ($sv as $f_idx => $f_data) {
				if ($sk == 'filter_groups') {
					$f_key = $f_data['filter_group_id'];
					
				} elseif ($sk == 'attributes') {
					$f_key = $f_data['attribute_id'];
					
				} elseif ($sk == 'options') {
					$f_key = $f_data['option_id'];

					if (isset($matches['required_options'][$f_key])) {
						$sets[$sk][$f_idx]['required'] = $matches['required_options'][$f_key];
					}
					
				} else {
					$f_key = (isset($f_data['field']) ? $f_data['field'] : $f_idx);
					
					if (isset($matches['set_default_for'][$f_key])) {
						$sets[$sk][$f_idx]['set_default_for'] = $matches['set_default_for'][$f_key];
					}
				}

				if (isset($matches[$sk][$f_key])) {
					if ($sk == 'options') {

						if (!empty($matches[$sk][$f_key]['fields'])) {
							foreach ($matches[$sk][$f_key]['fields'] as $mfk => $mfv) {
								if (isset($columns[$mfv])) {
									$sets[$sk][$f_idx]['fields'][$mfk]['column'] = $columns[$mfv];
									$sets[$sk][$f_idx]['expanded'] = true;
								}
							}
						}
						
						continue;
					}
				
					// the field can be array when
					// - it has language-specified values
					// - it may have many values like additional images
					//
					if (is_array($matches[$sk][$f_key])) {
						$sets[$sk][$f_idx]['column'] = array();

						foreach ($matches[$sk][$f_key] as $mfk => $mfv) {
							if (isset($columns[$mfv])) {
								$sets[$sk][$f_idx]['column'][$mfk] = $columns[$mfv];
							}
						}
					} else {
						if (isset($columns[$matches[$sk][$f_key]])) {
							$sets[$sk][$f_idx]['column'] = $columns[$matches[$sk][$f_key]];
						}
					}
				}
			}
		}

		return true;
	}
	
	/*
		- remove sets if they are not required in the current import
		- set default values
			
	*/
	protected function prepareSetsForImport(&$sets, $columns, $default) {

		// contains pairs
		// [column name] => <column_position>
		//
		$columns_in_use = array();
		foreach ($sets as $sk => $sv) {
		
			$has_column = false;
			foreach ($sv as $msk => $msv) {

				if ($sk == 'options') {
					if (empty($msv['fields'])) {
						continue;
					}
					$has_fields = false;
					foreach ($msv['fields'] as $fk => $fv) {
						if (isset($fv['column'])) {
							$columns_in_use[$columns[$fv['column']]] = $fv['column'];
							$has_fields = true;
						}
					}
					if (!$has_fields) {
						unset($sets[$sk][$msk]);
					}
					
				} elseif (isset($msv['column'])) {
					if (!is_array($msv['column'])) {
						$columns_in_use[$columns[$msv['column']]] = $msv['column'];
					} else {
						if (empty($msv['column'])) {
							unset($sets[$sk][$msk]);						
							continue;
						}
						foreach ($msv['column'] as $colk => $colv) {
							$columns_in_use[$columns[$colv]] = $colv;
						}
					}
					
				} elseif (!empty($msv['set_default_for'])) {

				} else {
					unset($sets[$sk][$msk]);
					continue;
				}

				$has_column = true;
				if (!empty($msv['set_default_for'])) {
					$sets[$sk][$msk]['default_value'] = $default[$msv['field']];
				}
			}
			
			if (!$has_column) {
				unset($sets[$sk]);
			}
		}

		return $columns_in_use;
	}
	
	public function getFieldSets($params) {
	
		$sets = $this->import_model->getFieldSets($params);
		
		return $sets;
	}
	
	public function initImport($params) {

		if (!$this->openFile($params)) {
			$this->report("initImport: file was not loaded. Last Error: " . $this->lastError);
			return false;
		}
		
		$this->session->data['ka_token'] = $this->session->data['user_token'];
		
		// clean up the temporary table
		//
		$this->db->query("DELETE FROM " . DB_PREFIX . "ka_product_import
				WHERE 
					token = '" . $this->session->data['ka_token'] . "'
					OR TIMESTAMPDIFF(HOUR, added_at, NOW()) > 168"
		);

		$params['delimiter'] = $this->getRealDelimiter($params['delimiter']);
		
		$this->params = $params;

		if (isset($this->params['matches'])) {
			unset($this->params['matches']);
		}
		if (isset($this->params['columns'])) {
			unset($this->params['columns']);
		}

		$this->params['images_dir'] = $this->kaformat->strip($this->params['images_dir'], array("\\", "/"));
		if (!empty($this->params['images_dir'])) {
			$this->params['images_dir'] = $this->params['images_dir'] . '/';
		}

		// store the relative path in 'incoming images' directory 
		// important: if incoming_images_dir exists then it should end with slash
		//
		$incoming_images_dir = '';
		if (!empty($this->params['incoming_images_dir'])) {
			$this->params['incoming_images_dir'] = $this->kaformat->strip($this->params['incoming_images_dir'], array("\\", "/"));
			if (!empty($this->params['incoming_images_dir'])) {
				$incoming_images_dir = $this->params['incoming_images_dir'] . '/';
			}
		}
		$this->params['incoming_images_dir'] = $incoming_images_dir;

		// prepare column data
		//
		$sets = $this->getFieldSets($this->params);
		
		$this->copyMatchesToSets($sets, $params['matches'], $params['columns']);
		
		$columns_in_use = $this->prepareSetsForImport($sets, $params['columns'], $params['default_values']);
		
		$this->params['matches'] = $sets;

		$this->params['status_for_new_products']      = $this->config->get('ka_product_import_status_for_new_products');
		$this->params['status_for_existing_products'] = $this->config->get('ka_product_import_status_for_existing_products');
		
		$this->params['cfg_options_separator']       = $this->config->get('ka_product_import_options_separator');
		$this->params['cfg_simple_option_separator'] = $this->config->get('ka_product_import_simple_option_separator');
		
		$this->params['parced_option_fields'] = array_flip(explode(';', $this->config->get('ka_product_import_simple_option_field_order')));
		
		$this->params['skip_img_download']            = $this->config->get('ka_product_import_skip_img_download');
		$this->params['cfg_image_separator']        = stripcslashes($this->config->get('ka_product_import_image_separator'));

		$this->params['cat_separator'] = $this->params['cat_separator'];
		$this->params['multicat_sep']  = $this->config->get('ka_product_import_multicat_separator');
		
		$this->params['opt_create_options']       = $this->config->get('ka_product_import_create_options');
		$this->params['opt_compare_as_is']        = $this->config->get('ka_product_import_compare_as_is');
		$this->params['opt_generate_seo_keyword'] = $this->config->get('ka_product_import_generate_seo_keyword');
		if (empty($this->params['cat_separator'])) {
			$this->params['cat_separator'] = '///';
		}
		
		$download_source_dir = '';
		if (!empty($this->params['download_source_dir'])) {
			$this->params['download_source_dir'] = $this->kaformat->strip($this->params['download_source_dir'], array("\\", "/"));
			if (!empty($this->params['download_source_dir'])) {
				$download_source_dir = dirname(DIR_APPLICATION) . DIRECTORY_SEPARATOR . $this->params['download_source_dir'] . '/';
			}
		}
		$this->params['download_source_dir'] = $download_source_dir;
		
		// set a short language code to the paramters
		// it will require for string converstion from national characters with KaUrlify::filter()
		//
		$language_code = $this->config->get('config_language_admin');
		$language_codes = explode('-', $language_code);
		if (!empty($language_codes)) {
			$this->params['language_code'] = $language_codes[0];
		}

		if (!empty($this->params['ir_import_group_id'])) {
			$this->params['replacement_columns'] = $this->kamodel_replacements->getReplacementColumns($this->params['ir_import_group_id'], $params['columns']);
		}

		if (!empty($this->params['sr_import_group_id'])) {
			$this->params['skip_rule_columns'] = $this->kamodel_skip_rule->getImportSkipRuleColumns($this->params['sr_import_group_id'], $params['columns']);
		}

		if (!empty($this->params['pr_import_group_id'])) {
			$this->params['price_rule_columns'] = $this->kamodel_price_rule->getImportPriceRuleColumns($this->params['pr_import_group_id'], $params['columns']);
		}
		
		$this->stat = array(
			'started_at' => time(),
			'filesize'   => filesize($params['file']),
			'offset'     => 0,
			
			'lines_processed'  => 0,
			'products_created' => 0,			
			'products_updated' => 0,
			'products_deleted' => 0,
			'products_hidden'     => 0,
			'products_disabled'   => 0,
			'categories_created'  => 0,
			'lines_skipped'       => 0,
			'price_rules_applied' => 0,

			'errors'           => array(),
			'status'           => 'not_started',
			'col_count'        => count($params['columns']),
		);

		// define stages
		$this->getStages();
		$this->stat = array_merge($this->stat, array(
			'completion_at'    => 0,
			'stage_id'         => 0,
			'stages_total'     => count($this->getStages()),
			'stage_completion' => 0,
		));

		$log_stat = $params;
		unset($log_stat['matches']);
		
		$this->kalog->write("Import started. Parameters: " . var_export($log_stat, true));

		return true;
	}

	
	public function getStages() {
	
		$stages = array(
			array(
				'code'     => 'import',
				'title'    => $this->language->get('File Processing'),
				'function' => array($this, 'runStageImport'),
			),
		);
		
		$model_stages = $this->import_model->getStages();
		if (!empty($model_stages)) {
			$stages = array_merge($stages, $model_stages);
		}
		
		$stages[] = array(
			'code'  => 'finalization',
			'title' => $this->language->get('Import Routine Finalization'),
			'function' => array($this, 'runStageFinal'),
		);		
		
		return $stages;	
	}
	

	public function getImportMessages() {
		return $this->messages;
	}

	
	public function getImportStat() {
	 	return $this->stat;
	}


	/*
		This function is supposed to be called from an external object multiple times. But first you
		will need to call initImport() to define import parameters.

		Import status can be determined by 
			$this->stat['status']  - completed, in_progress, error, not_started
			$this->stat['message'] - last import fatal error
		
		Import status can be checked by requesting getImportStat() function and verifying $status
		parameter.
	*/
	public function processImport() {

		if ($this->stat['status'] == 'completed') {
			return;
		}

		// switch error output to our stream
		//
		if (!defined('KA_DEBUG')) {
			$old_config_error_display = $this->config->get('config_error_display');
			$this->config->set('error_display', false);
		}

		// set timer
		//
		$this->started_at = time();
		
		$max_execution_time = @ini_get('max_execution_time');
		if ($max_execution_time > 5 && $max_execution_time < $this->sec_per_cycle) {
			$this->sec_per_cycle = $max_execution_time - 3;
		}

		$stages   = $this->getStages();
		$stage_id = $this->stat['stage_id'];
		$stage    = $stages[$stage_id];
		
		$this->import_model->setParams($this->params);
	
		try {
		
			// while the function returns 'true' we assume it needs more time to run
			//
			// - these functions do not change the stage
			// - they can throw an exception on unrecoverable error
			//
			if (!call_user_func($stage['function'])) {
				$stage_id++;
				if (empty($stages[$stage_id])) {
					$this->stat['stage_completion_at'] = 100;
					$this->stat['status'] = 'completed';
				} else { 
					$this->stat['stage_completion_at'] = 0;
					$this->stat['stage_id'] = $stage_id;
				}
			}

		} catch (\Exception $e) {
			$this->stat['status'] = 'error';
			$this->addImportMessage('Import error:' . $e->getMessage(), 'E');
		}
		
		if (!defined('KA_DEBUG')) {
			$this->config->set('error_display', $old_config_error_display);
		}

		return;
	}

	
	/*
		function updates $this->stat array.
		
	*/
	protected function runStageImport() {
	
		$this->load->model('tool/image');
	
		if (!empty($this->params['replacement_columns'])) {
			$this->replacements = $this->kamodel_replacements->initReplacementsCache($this->params['ir_import_group_id'], $this->params['replacement_columns']);
		}
		
		if (!empty($this->params['skip_rule_columns'])) {
			$this->skip_rules = $this->kamodel_skip_rule->initSkipRulesCache($this->params['sr_import_group_id'], $this->params['skip_rule_columns']);
		}

		if (!empty($this->params['price_rule_columns'])) {
			$this->price_rules = $this->kamodel_price_rule->initPriceRulesCache($this->params['pr_import_group_id'], $this->params['price_rule_columns']);
		}
		
		if (!$this->openFile($this->params)) {
			throw new \Exception("Cannot open file: " . $this->params['file']);
		}

		$col_count = $this->stat['col_count'];
		
		if ($this->stat['offset']) {
			if ($this->file->fseek($this->stat['offset']) == -1) {
				throw new \Exception("Cannot offset at $this->stat[offset] in file: $file.");
			}
		} else {
			$tmp = fgetcsv($this->file->handle, 0, $this->params['delimiter'], $this->enclosure, $this->escape);
			$this->stat['lines_processed'] = 1;
			if (is_null($tmp) || count($tmp) != $col_count) {
				throw new \Exception("File header does not match the initial file header.");
			}
		}

		while (1) {

			$this->entity_mark = "Line " . $this->stat['lines_processed'];
		
			// check if we are still in business
			if (empty($this->stat)) {
				throw new \Exception("Import script lost parameters. Aborting...");
			}

			if (time() - $this->started_at > $this->sec_per_cycle) {
				$status = 'in_progress';
				break;
			}

			$row = $this->getNextRow();
			if (is_null($row)) {
				// probably the file is finished
				break;
			}

			$this->stat['lines_processed']++;
			
			if (empty($row)) {
				$this->addImportMessage("Empty row skipped");
			}

			// make replacements of the column data if any exist
			//
			if (!empty($this->replacements)) {
				$this->applyReplacements($row);
			}

			// check if the record matches one of skip rules
			//
			if (!empty($this->skip_rules)) {
				if ($this->isRowSkipped($row)) {
					continue;
				}
			}

			$data = $this->getDataFromRow($row);

			// set the price multiplier
			//
			$this->price_multiplier = $this->getPriceMultiplier($row);
			
			if (!empty($data['model'])) {
				$this->entity_mark = $this->entity_mark . ' (model: ' . $data['model'] . ')';
			}
			
			// get entity id
			//
			$result = $this->import_model->getEntityId($data);
			if (empty($result)) {
				$this->addImportMessage($this->import_model->getLastError());
				continue;
			}
			$entity_id = (int)$result[0];
			
			// here we have several different ways how to process the product
			// - skip entity
			// - delete entity
			// - remove entity from the store
			// - go through insert/update procedure
			//
			if (!empty($entity_id)) {
		 		if (!$this->import_model->isImportable($entity_id)) {
					continue;
				}
			
				if (!empty($data['delete_product_flag'])) {
					$this->import_model->deleteEntity($entity_id);
					$this->stat['products_deleted']++;
					continue;
				}
			
				if (!empty($data['remove_from_store'])) {
					$this->removeFromStores('product', $entity_id, $this->params['store_ids']);
					$this->stat['products_updated']++;
					continue;
				}
			}
			
			$is_new_record   = false;	//
			$is_first_record = true;	// first occurence of the product in the file
			
			if (empty($entity_id)) {
				
				if (!empty($this->params['skip_new_products'])) {
					continue;
				}
				
				$entity_id = $this->import_model->createNewEntity($data);
				if (empty($entity_id)) {
					continue;
				}			

				$is_new_record = true;
				$this->stat['products_created']++;

			} else {
				// check if we already updated the entity
				//
				$qry = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "ka_product_import
					WHERE product_id = '$entity_id'
					AND token = '" . $this->session->data['ka_token'] . "'"
				);
				if (!empty($qry->rows)) {
					$is_first_record = false;
				}
			}

			if ($is_first_record) {
				$rec = array(
					'product_id' => $entity_id,
					'is_new'     => ($is_new_record ? 1 : 0),
					'token'      => $this->session->data['ka_token']
				);
				$this->kadb->queryInsert('ka_product_import', $rec);
			}

			$data['entity_id'] = $entity_id;
			
			try {
				$this->import_model->updateEntity($row, $data, $is_first_record, $is_new_record);
			} catch (\Exception $e) {
				$this->addImportMessage("Product update failed (" . $e->getMessage() . "'");
			}
		}

		$this->entity_mark = '';

		$this->stat['stage_completion_at'] = $this->stat['offset'] / ($this->stat['filesize'] / 100);
		
		if (feof($this->file->handle)) {
			$this->stat['offset'] = $this->stat['filesize'];
			$is_end = true;
		} else {
			$this->stat['offset'] = ftell($this->file->handle);
			$is_end = false;
		}
		
    	fclose($this->file->handle);
    	
    	// return 'true' when we have not finished yet
    	if (!$is_end) {
    		return true;
    	}
    	
   		// the stage is complete, the next stage can be started
	    return false;
	}


	public function runStageFinal() {
	
    	// rename the import file if required
    	//
    	if ($this->params['location'] == 'server' && !empty($this->params['rename_file'])) {
	    	$path_parts = pathinfo($this->params['file']);
    	
	    	$dest_file  = $path_parts['dirname'] . DIRECTORY_SEPARATOR . $path_parts['filename'] 
				. '.' . 'processed_at_' . date("Ymd-His") 
				. '.' . $path_parts['extension'];
			if (!rename($this->params['file'], $dest_file)) {
				$this->addImportMessage("rename operation failed. from " .$this->params['file'] . " to " . $dest_file);
			}
		}
		
		// clean up the temporary table
		//
		$this->db->query("DELETE FROM " . DB_PREFIX . "ka_product_import
				WHERE token = '" . $this->session->data['ka_token'] . "'"
		);

    	$this->kalog->write("Import completed. Parameters: " . var_export($this->stat, true));
	}

	
	public function getCustomerGroupByName($customer_group) {

		static $customer_groups;

		if (isset($customer_groups[$customer_group])) {
			return $customer_groups[$customer_group];
		}

		$qry = $this->db->query("SELECT cgd.customer_group_id FROM " . DB_PREFIX . "customer_group cg
			INNER JOIN " . DB_PREFIX . "customer_group_description cgd
				ON cg.customer_group_id = cgd.customer_group_id
			WHERE
				cgd.name = '$customer_group'"
		);

		
		if (empty($qry->row)) {
			$customer_groups[$customer_group] = 0;
			return 0;
		}
		
		$customer_groups[$customer_group] = $qry->row['customer_group_id'];
						
		return $qry->row['customer_group_id'];
	}
	
	
 	public function report($msg) {

 		if (defined('KA_DEBUG')) {
 			echo $msg;
 		}

		$this->kalog->write($msg);
 	}

 	
	public function addImportMessage($msg, $type = 'W') {
		static $too_many = false;

		if ($too_many) return false;

		$prefix = '';
		if ($type == 'W') {
			$prefix = 'WARNING';
		} else if ($type == 'E') {
	  		$prefix = 'ERROR';
	  	} elseif ($type == 'I') {
		  	$prefix = 'INFO';
		}

		if (!empty($this->messages) && count($this->messages) > 150) {
			$too_many = true;
	  		$msg = "too many messages...";
	  	} else {
	  		if (!empty($this->entity_mark)) {
				$prefix .= ': ' . $this->entity_mark;
			}
		  	$msg = $prefix . ': ' . $msg;
		}

		$this->messages[] = $msg;
	}

	
	public function insertToStores($entity, $entity_id, $stores) {

		$table = $entity . "_to_store";
		$field = $entity . "_id";

		foreach($stores as $sv) {
		 	$rec = array(
		 		'store_id' => $sv,
		 		$field => $entity_id
		 	);
		 	$this->kadb->queryInsert($table, $rec, true);
		}
	}
	

	protected function removeFromStores($entity, $entity_id, $stores) {

		$table = $entity . "_to_store";
		$field = $entity . "_id";

		if (!is_array($stores)) {
			$stores = array($stores);
		}
		
		if (empty($stores)) {
			return false;
		}

		foreach($stores as $sv) {
			$this->db->query("DELETE FROM " . DB_PREFIX . $table . " WHERE 
				$field = '" . intval($entity_id) . "' AND store_id = '" . intval($sv) . "'"
			);
		}

		return true;
	}
	
	
	/*
		Some urls may omit the protocol but we can detect them anyway
	*/
	function isUrl($path) {
    	return preg_match('/^(http:|https:|ftp:|)\/\//isS', $path);
	}

	protected function normalizeFilename($filename) {

		$chars = array('\\','/','=','.','+','*','?','[','^',']','(','$',']','&','<','>', ';');
   		$filename = str_replace($chars, "_", $filename);

   		return $filename;
	}

	/*
		RETURNS:
			$file - a path with file name within the 'image' directory or FALSE on error.

		The file name can be theortically converted to the right charset but we do not support
		it at this moment.
		$file  = iconv('utf-8', 'Windows-1251', $image);
	*/
	public function getImageFile($image) {
		$this->lastError = '';
		
		if (empty($image))
			return false;

		$image = trim($image);
		
		$is_google_file = false;
		
		$file = '';
		if ($this->isUrl($image)) {

			// experimental feature to download Google images
			//
			// replace drive images like this: 
			//   https://drive.google.com/file/d/abcdefmrp6IpP2TQ7YKQd8hs8KhO5e3Aoh
			// to urls like this: 
			//   https://drive.google.com/u/0/uc?id=abcdefmrp6IpP2TQ7YKQd8hs8KhO5e3Aoh&export=download
			//
			if (strpos($image, 'drive.google.com/file/d/') !== false) {
				if (preg_match("/file\/d\/([\-_A-Za-z0-9]*)/", $image, $matches)) {
					$image = "https://drive.google.com/u/0/uc?id=" . $matches[1] . "&export=download";
					$is_google_file = true;
				}
			}
		
			// parse the image URL
			//
		    $url_info = @$this->mb_parse_url($image);

		    if (empty($url_info)) {
	    		$this->lastError = "Invalid URL data $url";
	    		return false;
			}
			
			if (empty($url_info['scheme'])) {
				$url_info['scheme'] = 'http';
			}

	    	// get a relative image directory to $images_dir
			//
		    $fullname  = '';
		    $images_dir = str_replace("\\", '/', $this->params['incoming_images_dir']);
		    
		    // if the path exists in the URL then parse it
		    // to the 'path' and 'filename'
		    //
	    	if (!empty($url_info['path'])) {
	    	
	    		$url_info['path'] = urldecode($url_info['path']);
	    		
			    $path_info = pathinfo($url_info['path']);

			    // create a directory path for the image
			    //
			    $path_info['dirname'] = $this->kaformat->strip($path_info['dirname'], array("\\","/"));

			    if (!empty($path_info['dirname'])) {
		    		$images_dir = $images_dir . $path_info['dirname'] . '/';

					$dirname = DIR_IMAGE . $images_dir;
					if (strpos($dirname, '//') !== false) {
						$this->lastError = 'Invalid image path:' . $dirname;
						return false;
					}

		    		if (!file_exists($dirname)) {		    		
		    			try {
		    				if (!mkdir($dirname, 0755, true)) {
		    					throw new \Exception('mkdir failed');
		    				}
		    			} catch (\Exception $e) {
		    				$this->lastError = "Script cannot create directory: $dirname";
		    				return false;
		    			}
			    	}
			    }

			    // normalize the image name if it consists of international characters
			    //
			    if (!empty($path_info['filename'])) {
			    	$path_info['filename'] = KaUrlify::filter($path_info['filename'], 256, $this->params['language_code']);
			    	$path_info['basename'] = $path_info['filename'];
			    	if (!empty($path_info['extension'])) {
			    		$path_info['basename'] .= '.' . strtolower($path_info['extension']);
			    	}
			    }

			    // skip downloading files if they exist on the server
			    // it works for direct URLs only
			    //
			    if (!empty($this->params['skip_img_download'])) {
					if (empty($url_info['query']) && !empty($path_info['extension'])) {
						$_file = $images_dir . $path_info['basename'];
						if (is_file(DIR_IMAGE . $_file) && filesize(DIR_IMAGE . $_file) > 0) {
							return $_file;
						}
					}
				}
			}

			$max_filename_length = 256 - strlen(DIR_IMAGE . $images_dir) - 4;
			if ($max_filename_length < 25) {
				$this->lastError = "File path is too long. A file cannot be created.";
				return false;
			}
					
			// 2) download the file
			//
			$image = htmlspecialchars_decode($image);
			
		    $content = $this->kacurl->getFileContentsByUrl($image);
	    	if (empty($content)) {
		    	$this->lastError = "File content is empty for $image (" . $this->lastError . ")";
		    	return false;
	    	}

	    	// for compatibility with older ka-ext libraries we check the method presence
	    	// it can be removed later
	    	$file_info = array();
	    	if (method_exists($this->kacurl, 'getLastFileInfo')) {
		    	$file_info = $this->kacurl->getLastFileInfo();
		    }

	    	// save the image to a temporary file
	    	//
		  	$tmp_file = tempnam(DIR_IMAGE . $images_dir, "tmp");

		  	$size = file_put_contents($tmp_file, $content);
		  	if (empty($size)) {
		  		$this->lastError = "Cannot save the image file: $tmp_file";
			  	return false;
			}

			try {
	    		$image_info = getimagesize($tmp_file);
	    	} catch (\Exception $e) {
	    		$image_info = false;
	    	}
	    	
    		if (empty($image_info)) {
				$this->lastError = "getimagesize returned empty info for the file: $image";
				return false;
			}

			// 3) get a complete image file path
			//
			if (!empty($file_info['filename']) && $is_google_file) {
			
				$filename = $file_info['filename'];

			} else if (!empty($url_info['query'])) {
				$filename = '';
				if (!empty($path_info['basename'])) {
					$filename = $path_info['basename'];
				}
				
				$query = $this->normalizeFilename($url_info['query']);
				
				$filename = $filename . $query;

				// if a 'copy' parameter exceeds 256 characters, we have to generate a shorter filename
				//
				if (strlen($filename) > $max_filename_length) {
					$filename = uniqid('', true);
				}
				
				$filename = $filename . image_type_to_extension($image_info[2]);
				
			} else {
				$filename = $path_info['basename'];
				if (empty($path_info['extension'])) {
					$filename = $filename . image_type_to_extension($image_info[2]);
				}
			}

			// 4) move the image file to the incoming directory
			//
			if (is_file(DIR_IMAGE . $images_dir . $filename)) {
				@unlink(DIR_IMAGE . $images_dir . $filename);
			}
			
			if (!is_file(DIR_IMAGE . $images_dir . $filename)) {
				if (!rename($tmp_file, DIR_IMAGE . $images_dir . $filename)) {
					$this->lastError = "rename operation failed. from $tmp_file to " . DIR_IMAGE . $images_dir . $filename;
					return false;
				}

				if (!chmod(DIR_IMAGE . $images_dir . $filename, 0644)) {
					$this->lastError = "chmod failed for file: $filename";
					return false;
				}
			}

		   	$file = $images_dir . $filename;
		   	
		} else {
			
			//
			// if the image is a regular file
			//
			$file = $this->params['images_dir'] . htmlspecialchars_decode($image);
			if (!is_file(DIR_IMAGE . $file)) {
				$this->lastError = "File was not found " . DIR_IMAGE . $file;
				return false;
			}
		}

		return $file;
	}


	/*
		Split images from one field
	*/
	public function splitImages($str) {

		$images = array();

		// try standard image delimiter
		//
		if (strlen($this->params['cfg_image_separator'])) {
			if (stripos($str, $this->params['cfg_image_separator']) !== false) {
				$images = explode($this->params['cfg_image_separator'], $str);
				return $images;
			}
		}
		
		// try semicolon
		//
		if (stripos($str, ';') !== false) {
			$images = explode(';', $str);
			return $images;
		}
		
		$images[] = $str;
		
		return $images;
	}

	
    /*
      parse_url() function for multi-bytes character encodings
      
    */
    function mb_parse_url($url, $component = -1)
    {
    	$encodedUrl = preg_replace_callback('/[^$:\/@?&=#]+/usD', function ($matches) {
    		return urlencode($matches[0]);
    	}, $url);
    	
    	$parts = parse_url($encodedUrl, $component);
    	
    	if ($parts === false) {
    		throw new \InvalidArgumentException('Malformed URL: ' . $url);
    	}
    	
    	if (is_array($parts) && count($parts) > 0) {
    		foreach ($parts as $name => $value) {
    			$parts[$name] = rawurldecode($value);
    		}
    	}
    	
    	return $parts;
    }

	
	/*
		The function tries to find the replacement when it is not available in cache.
		
		RETURN:
			null  - when the replacement is not found
			<val> - new column value 
	*/
	public function findReplacement($column_name, $column_value) {
		
		$qry = $this->db->query("SELECT * FROM " . DB_PREFIX . "ka_import_replacements WHERE
			column_name = '" . $this->db->escape($column_name) . "' 
			AND old_value = '" . $this->db->escape($column_value) . "'
			LIMIT 1
		");
			
		if (empty($qry->rows)) {
			return null;
		}

		array_shift($this->replacements[$column_name]['cache']);
		$this->replacements[$column_name]['cache'][$qry->row['old_value']] = $qry->row['new_value'];
		
		return $qry->row['new_value'];
	}

	
	protected function applyReplacements(&$row) {

		$is_applied = false;
		foreach ($this->replacements as $rk => $rv) {
			if (isset($rv['cache'][$row[$rv['column']]])) {
				$row[$rv['column']] = $rv['cache'][$row[$rv['column']]];
			} elseif (!$rv['cache_only']) {
				$replacement = $this->findReplacement($rk, $row[$rv['column']]);
				if (!is_null($replacement)) {
					$row[$rv['column']] = $replacement;
					$is_applied = true;
				}
			}
		}
		
		return $is_applied;
	}	
	
	
	protected function isRowSkipped($row) {
/*				
			$skip_rules =  array(
				'column1' => array(
					'column' => 1,
					'values_cache' => 
						'val1' => ACTION
						'val2' => action
						
					'rules' => 
						0 => array(
							'pattern'
							'rule_action'
						),
						1 => array(
							'pattern2'
							'rule_action2'
						)
				)
			);
*/
		// loop through all columns defined for skip rules
		//
		// 512 - max hash length stored in the cache
		//
		foreach ($this->skip_rules as $srk => $srv) {
		
			$val = $row[$srv['column']];
			
			// we specify here a "unique" value to get a key for a hash
			//
			if (strlen($val) == 0) {
				$val = '!!!empty!!!';
			}
			
			// we skip rules if there is an existing result in the column cache
			//
			if (!empty($val) && mb_strlen($val) < 512) {
				if (!empty($srv['values_cache'][$val])) {
					if ($srv['values_cache'][$val] == 'I') {
						break;
					} else {
						$this->stat['lines_skipped']++;
						return true;
					}
				}
			}
			
			// loop through rules to find if we have any rule matching the cell data
			//
			foreach ($srv['rules'] as $rule) {
				$action = array();

				if ($this->kamodel_skip_rule->isValueMatched($rule['pattern'], $val)) {
				
					if ($rule['rule_action'] == 'I') {
						// exit both loops and continue line processing
						if (!empty($val) && mb_strlen($val) < 512) {
							$this->skip_rules[$srk]['values_cache'][$val] = 'I';
							if (count($this->skip_rules[$srk]['values_cache']) > 30) {
								array_shift($this->skip_rules[$srk]['values_cache']);
							}
						}
						break 2;
					} else {
						// go to the next csv file line
						if (!empty($val) && mb_strlen($val) < 512) {
							if (count($this->skip_rules[$srk]['values_cache']) > 30) {
								array_shift($this->skip_rules[$srk]['values_cache']);
							}
							$this->skip_rules[$srk]['values_cache'][$val] = 'E';
						}
						$this->stat['lines_skipped']++;
						return true;
					}
				}
			}
		}
		
		return false;
	}	

	
	protected function getPriceMultiplier($row) {
	
		$price_multiplier = $this->params['price_multiplier'];
		
		// update the price multiplier according to price rules if applicable
		//
		if (!empty($this->price_rules)) {
		
			foreach ($this->price_rules as $prk => $prv) {
			
				$val = $row[$prv['column']];
				// we specify here a "unique" value to get a key for a hash
				if (strlen($val) == 0) {
					$val = '!!!empty!!!';
				}
		
				// we skip rules if there is an existing result in the column cache
				//
				if (!empty($val) && mb_strlen($val) < 512) {
					if (!empty($prv['values_cache'][$val])) {
						$this->price_multiplier = $prv['values_cache'][$val];
						$this->stat['price_rules_applied']++;
						break;
					}
				}				

				// loop through rules to find if we have any rule matching the cell data
				//
				foreach ($prv['rules'] as $rule) {
					$action = array();

					if ($this->kamodel_price_rule->isValueMatched($rule['pattern'], $val)) {
						$this->stat['price_rules_applied']++;
						$price_multiplier = $rule['price_multiplier'];
						if (!empty($val) && mb_strlen($val) < 512) {
							$this->price_rules[$prk]['values_cache'][$val] = $rule['price_multiplier'];
							if (count($this->price_rules[$prk]['values_cache']) > 30) {
								array_shift($this->price_rules[$prk]['values_cache']);
							}
						}
						break 2;
					}
				}
			}
		}
		
		return $price_multiplier;
	}
	
	
	protected function getDataFromRow($row) {
	
		$data = array();
	
		foreach ($this->params['matches']['fields'] as $fk => $fv) {
			if (!isset($fv['column']) && !isset($fv['default_value']))
				continue;

			if (isset($fv['column']) && is_array($fv['column'])) {
				$data[$fv['field']] = array();

				foreach ($fv['column'] as $ck => $column_index) {
					if (empty($row[$column_index])) {
						continue;
					}
					$data[$fv['field']][$ck] = trim($row[$column_index]);
				}
			} elseif (isset($fv['column'])) {
				// add prefix to key fields
				if (!empty($this->params['key_field_prefix'])) {
					if (in_array($fv['field'], $this->key_fields)) {
						$row[$fv['column']] = $this->params['key_field_prefix'] . $row[$fv['column']];
					}
				}
				$data[$fv['field']] = trim($row[$fv['column']]);
			
				// set a default value for empty cells only
				// we assume that the column is not specified for set_default_for = 'A'
				//
				if (isset($fv['default_value'])) {
					if (empty($data[$fv['field']])) {
						$data[$fv['field']] = $fv['default_value'];
					}
				}
			} else {
				// set a default value for all rows in the column (when the column is not specified)
				//
				$data[$fv['field']] = $fv['default_value'];
			}
		}
		
		return $data;
	}
	
	
	public function registerRecord($product_id, $type, $id) {
		$rec = array(
			'product_id' => $product_id,
			'record_type' => $type,
			'record_id'  => $id,
			'token' => $this->session->data['ka_token'],
		);
		$this->kadb->queryInsert('ka_import_records', $rec, true);
	}
	
	public function isRecordRegistered($product_id, $type) {
	
		$result = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "ka_import_records WHERE
			product_id = '" . (int)$product_id . "'
			AND record_type = '" . $type . "'
			AND token = '" . $this->session->data['ka_token'] . "'
		")->row;
		
		if (empty($result['total'])) {
			return false;
		}
		
		return true;		
	}
	

	/*
		Return row or null if the file is over
		it may 
	*/
	protected function getNextRow() {

		if (!($row = fgetcsv($this->file->handle, 0, $this->params['delimiter'], $this->enclosure, $this->escape))) {
			return null;
		}

		if (empty($row)) {
			return array();
		}
		
		$col_count = $this->stat['col_count'];
		
		$row = $this->request->clean($row);
		
		// compare number of read values against the number of columns in the header
		//
		$row_count = count($row);
		if ($row_count < $col_count) {
			if ($row_count == 1) {
				return array();
			}

			// extend the line with empty values. MS Excel may 'optimize' a CSV file and remove
			// trailing empty cells
			//
			$tail = array_fill($row_count, $col_count - $row_count, '');
			$row = array_merge($row, $tail);
			
		} elseif ($row_count > $col_count) {
			$row = array_slice($row, 0, $col_count);
		}

		return $row;
	}
	
	
	public function unclean($text) {
	
		$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
		
		return $text;
	}
	
	
	public function isTimeout() {
	
		if (time() - $this->started_at > $this->sec_per_cycle) {	
			return true;
		}
		
		return false;
		
	}
	
}
