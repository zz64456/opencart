<?php 
/*
 $Project: CSV Product Import $
 $Author: karapuz team <support@ka-station.com> $
 $Version: 6.0.0.2 $ ($Revision: 581 $) 
*/
namespace extension\ka_product_import;

use \extension\ka_extensions\KaGlobal;

class ControllerImport extends \extension\ka_extensions\ControllerForm { 

	protected $tmp_dir;
	protected $store_root_dir;
	protected $store_images_dir;
	protected $kaformat = null;

	public $params;

	protected static $max_visible_options  = 100;
	protected static $max_expanded_options = 30;
	
	private $kamodel_import;
	private $kamodel_import_product;
	private $kamodel_import_profiles;
	private $kamodel_import_groups;
	private $kamodel_replacements;
	
	protected function getFields() {
		return array();
	}
	
	protected function getPageUrlParams() {
		return array();
	}
	
	protected function onLoad() {

		parent::onLoad();

		$this->params = &$this->getSession('params');
	
		$this->kaformat = new \extension\ka_extensions\Format($this->config->get('config_language_id'));

		$this->tmp_dir          = DIR_CACHE;
		$this->store_root_dir   = dirname(DIR_APPLICATION);
		$this->store_images_dir = dirname(DIR_IMAGE) . DIRECTORY_SEPARATOR . basename(DIR_IMAGE);
		
 		$this->load->language('extension/ka_product_import/common');
 		$this->load->language('extension/ka_product_import/import');
		
		$this->kamodel_import          = $this->load->kamodel('extension/ka_product_import/import');
		$this->kamodel_import_profiles = $this->load->kamodel('extension/ka_product_import/import_profiles');
		$this->kamodel_import_group    = $this->load->kamodel('extension/ka_product_import/import_group');
		$this->kamodel_replacements    = $this->load->kamodel('extension/ka_product_import/replacements');
		
		$this->kamodel_import_product  = $this->load->kamodel('extension/ka_product_import/import/product');

		$this->kamodel_import->setImportModel($this->kamodel_import_product);
				
		$this->data['heading_title']    = $this->language->get('txt_form_page_title');

		$this->data['store_images_dir'] = $this->store_images_dir;
		$this->data['store_root_dir']   = $this->store_root_dir;

		$this->addBreadcrumb($this->language->get('txt_form_page_title'), $this->url->linka('extension/ka_product_import/import', $this->url_params->getUrl()));
	}


	/*
		Step 1 page controller
	*/
	public function index() {

		$this->load->language('extension/ka_product_import/step1');

		$this->load->model('catalog/product');
		
		// do we need to re-install the extension?
		//
		if (!$this->kamodel_import->isDBPrepared()) {
			$this->data['is_wrong_db'] = true;
			$this->showPage('extension/ka_product_import/error');
			return;
		}

		// stop any existing imports
		$this->kamodel_import->resetStat();
		
		// set import parameters
		//
		$this->params = $this->getImportParameters();
		
		// get import profiles
		//
		$profiles = $this->kamodel_import_profiles->getProfiles();
		$this->data['profiles'] = $profiles;

		// get import groups
		//
		$import_groups = $this->kamodel_import_group->getImportGroups();

		// process the form submission
		//
		if ($this->request->server['REQUEST_METHOD'] == 'POST') {

			if (!isset($this->request->post['images_dir'])) {
				$this->addTopMessage($this->language->get("Wrong post parameters. Plea..."), 'E');
				$this->session->data['save_params'] = true;
			 	return $this->response->redirect($this->url->linka('extension/ka_product_import/import'));
			}
			
			// check the 'incoming images' directory
			//
			$incoming_images_dir = $this->store_images_dir . '/' . $this->request->post['incoming_images_dir'];
			if (!is_dir($incoming_images_dir)) {
				mkdir($incoming_images_dir, 0775, true);
			}
			if (!is_dir($incoming_images_dir)) {
				$this->addTopMessage($this->language->get("The Incoming images direc..."), 'E');
				$this->session->data['save_params'] = true;
			 	return $this->response->redirect($this->url->linka('extension/ka_product_import/import'));
			}

			// process profile submission
			//
			$msg = '';
			if ($this->request->post['mode'] == 'load_profile') {
			
				$this->session->data['save_params'] = true;
				$this->params = array_merge($this->params, $this->kamodel_import_profiles->getProfileParams($this->request->post['profile_id']));
				
				if (!empty($this->params) && !empty($this->request->post['profile_id'])) {
					$this->params['profile_id'] = $this->request->post['profile_id'];
					
					if ($this->params['location'] == 'local') {
						$this->params['file_name'] = '';
						$this->params['file'] = '';
					}

					$this->addTopMessage($this->language->get("Profile has been loaded suc..."));
				} else {
					$this->addTopMessage($this->language->get("Profile was not loaded"), 'E');
				}
				
				return $this->response->redirect($this->url->linka('extension/ka_product_import/import'));
				
			} elseif ($this->request->post['mode'] == 'delete_profile') {
			
				$this->kamodel_import_profiles->deleteProfile($this->request->post['profile_id']);
				$this->session->data['save_params'] = true;
				$this->addTopMessage($this->language->get("Profile has been deleted su..."));
				
				return $this->response->redirect($this->url->linka('extension/ka_product_import/import'));
			}
		
			// save submitted parameters
			//
			$this->params['images_dir']          = $this->request->post['images_dir'];
			$this->params['incoming_images_dir'] = $this->request->post['incoming_images_dir'];
			$this->params['location']            = $this->request->post['location'];
			$this->params['cat_separator']       = $this->request->post['cat_separator']; 
			$this->params['update_mode']         = $this->request->post['update_mode']; 
			$this->params['price_multiplier']    = doubleval(str_replace(',', '.', $this->request->post['price_multiplier']));
			$this->params['rename_file']         = (!empty($this->request->post['rename_file'])) ? true:false;

			// delimiter
			$this->params['delimiter_option'] = $this->request->post['delimiter_option'];
			if ($this->params['delimiter_option'] == 'predefined') {
				$this->params['delimiter'] = $this->request->post['delimiter']; 
			} else {
				$this->params['delimiter'] = trim($this->request->post['custom_delimiter']); 
			}
			
			// charset
			$this->params['charset_option'] = $this->request->post['charset_option'];
			if ($this->params['charset_option'] == 'predefined') {
				$this->params['charset'] = $this->request->post['charset'];
			} else {
				$this->params['charset'] = $this->request->post['custom_charset'];
			}

			// stores
			if (!empty($this->request->post['store_ids'])) {
				$this->params['store_ids'] = $this->request->post['store_ids'];
			} else {
				$this->params['store_ids'] = array(0);
			}
			
			$this->params['disable_not_imported_products'] = (isset($this->request->post['disable_not_imported_products'])) ? true : false;
			$this->params['add_to_each_category']          = (isset($this->request->post['add_to_each_category'])) ? true : false;
			$this->params['skip_new_products']    = (isset($this->request->post['skip_new_products'])) ? true : false;
			$this->params['import_as_plain_text'] = (isset($this->request->post['import_as_plain_text'])) ? true : false;
			$this->params['download_source_dir']  = $this->request->post['download_source_dir'];
			$this->params['file_name_postfix']    = $this->request->post['file_name_postfix'];
			$this->params['key_field_prefix']     = $this->request->post['key_field_prefix'];

			if (isset($this->request->post['default_category_id'])) {
				$this->params['default_category_id'] = (int) $this->request->post['default_category_id'];
			}
			if (isset($this->request->post['parent_category_id'])) {
				$this->params['parent_category_id'] = (int) $this->request->post['parent_category_id'];
			}
			if (isset($this->request->post['sr_import_group_id'])) {
				$this->params['sr_import_group_id'] = (int) $this->request->post['sr_import_group_id'];
			}
			if (isset($this->request->post['pr_import_group_id'])) {
				$this->params['pr_import_group_id'] = (int) $this->request->post['pr_import_group_id'];
			}
			if (isset($this->request->post['ir_import_group_id'])) {
				$this->params['ir_import_group_id'] = (int) $this->request->post['ir_import_group_id'];
			}

			if ($this->params['location'] == 'server') {
				$this->params['file_path'] = $this->kaformat->strip($this->request->post['file_path'], array('/','\\'));
				$this->params['file']      = $this->store_root_dir . DIRECTORY_SEPARATOR . $this->params['file_path'];

				if (!file_exists($this->params['file'])) {
					$msg = $msg . $this->language->get('error_file_not_found');
				}

			} else {
			
				if (!empty($this->request->post['is_file_uploaded'])) {
				
					if (!file_exists($this->params['file'])) {
						$this->params['file'] = '';
						$this->params['file_name'] = '';
					}
				
				} elseif (!empty($this->request->files['file']) && is_uploaded_file($this->request->files['file']['tmp_name'])) {
				
					$filename = $this->request->files['file']['name'] . '.' . md5(rand());
					if (move_uploaded_file($this->request->files['file']['tmp_name'], $this->tmp_dir . $filename)) {
					  $this->params['file']      = $this->tmp_dir . $filename;
					  $this->params['file_name'] = $this->request->files['file']['name'];
					} else {
						$msg = $msg . str_replace('{dest_dir}', $this->tmp_dir, $this->language->get('error_cannot_move_file'));
					}
				}
				
				if (empty($this->params['file'])) {
					$msg = $msg . $this->language->get('error_file_not_found');
			 	}
		 	}
		 	
			if (!empty($this->request->post['tpl_product_id'])) {
				$product = $this->model_catalog_product->getProduct($this->request->post['tpl_product_id']);
				if (empty($product)) {
					$msg .= "Template product was not found";
				} else {
					$this->params['tpl_product_id'] = $product['product_id'];
				}
			} else {
				$this->params['tpl_product_id'] = 0;
			}

		 	if (empty($msg)) {
				$params = $this->params;
				
				if ($this->kamodel_import->openFile($params)) {
					$this->params['columns'] = $this->kamodel_import->readColumns($params['delimiter']);

					// remove columns with an empty name from the columns list
					$columns = array_diff($this->params['columns'], array(""));
					
					if (count($columns) < 1) {
						$msg .= $this->language->get("Wrong field delimiter or in...");
					}
					
					if (count(array_unique($columns)) < count($columns)) {
						$msg .= $this->language->get("There are duplicated column...");
					}
					
				} else {
					$msg .= $this->kamodel_import->getLastError();
				}
			}
			
			if (!empty($msg)) {
				$this->addTopMessage($msg, 'E');
				$this->session->data['save_params'] = true;
			 	return $this->response->redirect($this->url->linka('extension/ka_product_import/import', 'user_token=' . $this->session->data['user_token'], true));
			}
			
			return $this->response->redirect($this->url->linka('extension/ka_product_import/import|step2', 'user_token=' . $this->session->data['user_token'], true));
		}

		// constant parameters
		//
		$this->params['opt_enable_macfix']                = $this->config->get('ka_product_import_enable_macfix');
		$this->params['parse_simple_option_value_as_csv'] = $this->config->get('ka_product_import_parse_simple_option_value_as_csv');
		
		$this->session->data['save_params'] = false;
		$this->params['step'] = 1;
		
		$this->load->model('setting/store');
		$this->data['stores'] = $this->kamodel_import->getStores();

		$this->load->model('catalog/category');
		if ($this->model_catalog_category->getTotalCategories() < 1000) {
 			$filter_data = array(
 				'sort'  => 'name',
 				'order' => 'asc',
 			);
 			$this->data['categories'] = $this->model_catalog_category->getCategories($filter_data);
		} else {
			$this->data['categories'] = array();
		}
		
		$this->data['charsets']   = $this->kamodel_import->getCharsets();
		$this->data['delimiters'] = $this->kamodel_import->getDelimiters();
		$this->data['import_groups'] = $import_groups;
		
		// check the file compatibility
		//
		if (!empty($this->params['file']) && file_exists($this->params['file']) && is_file($this->params['file'])) {
		
			$file_data = file_get_contents($this->params['file'], false, NULL, 0, 1024 * 8);
			$results = $this->kamodel_import->examineFileData($file_data);
		
			if ($results['charset'] == $this->params['charset']) {
				$this->data['charset_is_ok'] = true;
			}
			
			if ($results['delimiter'] == $this->params['delimiter']) {
				$this->data['delimiter_is_ok'] = true;
			}
		}

		// get tpl product information
		if (!empty($this->params['tpl_product_id'])) {
			$product = $this->model_catalog_product->getProduct($this->params['tpl_product_id']);
			if (!empty($product)) {
				$this->data['tpl_product'] = $product;
			}
		}
		
		// get languages
		//
		$this->load->model('localisation/language');
		$this->data['languages'] = $this->model_localisation_language->getLanguages();
		
		$this->data['action_next'] = $this->url->linka('extension/ka_product_import/import');
		$this->data['backup_link'] = $this->url->linka('tool/backup');
		
		// pass parameters to the template
		$this->data['params']    = $this->params;
		
		$key_fields = $this->kamodel_import_product->getKeyFields();
		$this->data['key_fields'] = implode(", ", $key_fields);
		
		$this->data['max_server_file_size'] = $this->kamodel_import->getUploadMaxFilesize();
		$this->data['max_file_size']        = $this->kaformat->convertToMegabyte($this->kamodel_import->getUploadMaxFilesize());

		$this->data['product_url']  = $this->url->linka('catalog/product|form');
		$this->data['settings_url'] = $this->url->linka('extension/ka_product_import/extension');
		
		$this->document->setTitle($this->language->get('txt_form_page_title') . ': ' . $this->language->get('STEP 1 of 3'));
		
		$this->showPage('extension/ka_product_import/step1');
	}


	/*
		ajax call to stop showig the caution message for the current user session
	*/
	public function saveWarning() {
		$this->session->data['hide_backup_warning'] = true;
	}
	
	/*
		ajax call to return a list of products matching the passed filter
	*/
	public function completeTpl() {
		$json = array();
		
		if (isset($this->request->post['filter_name'])) {
			$this->load->model('catalog/product');
			
			$data = array(
				'filter_name' => $this->request->post['filter_name'],
				'start'       => 0,
				'limit'       => 20
			);
			
			$results = $this->model_catalog_product->getProducts($data);
			
			foreach ($results as $result) {
				$option_data = array();
				
				$json[] = array(
					'product_id' => $result['product_id'],
					'name'       => html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'),	
					'model'      => $result['model'],
					'price'      => $result['price']
				);	
			}
		}
		
		$this->response->setOutput(json_encode($json));
	}
	
	
	/*
		PARAMETERS:
			file - string of 1-10Kb data with file data.
			
		RETURNS:
			charset   - empty or string with charset code (value from the select box)
			delimiter - empty or value from the select box
			error     - string. If error is not empty then show the error text to the user
			
	*/
	public function examineFileData() {
		$json = array();

		if (isset($this->request->post['file_data'])) {

			$file_data = base64_decode($this->request->post['file_data']);
			$file_data = substr($file_data, 0, 1024);
		
			$data = $this->kamodel_import->examineFileData($file_data);
		
			if (!empty($data['error'])) {
				$json = array(
					'error' => $data['error'],
				);
				
			} else {
				$json = array(
					'charset'   => html_entity_decode($data['charset'], ENT_QUOTES, 'UTF-8'),
					'delimiter' => html_entity_decode($data['delimiter'], ENT_QUOTES, 'UTF-8'),
					'error'     => '',
				);
			}
		} else {
			$json = array(
				'error' => 'File data is not found',
			);
		}

		$this->response->setOutput(json_encode($json));
	}


	/*
		Step 2 page controller
	*/
	public function step2() { // step2

		$this->load->language('extension/ka_product_import/step1');
		$this->load->language('extension/ka_product_import/step2');
		
		$this->params['step'] = 2;

		// check if we have full file information
		if (empty($this->params['columns'])) {
			$this->addTopMessage($this->language->get("Do not open the extension p..."), "E");
			return $this->response->redirect($this->url->linka('extension/ka_product_import/import'));
		}
		
		// get the columns array
		//
		$this->data['columns'] = $this->params['columns'];
		array_unshift($this->data['columns'], '');
		$this->data['columns'] = array_unique($this->data['columns']);

		// process post if required
		//
		if (($this->request->server['REQUEST_METHOD'] == 'POST')) {

			$sets = $this->kamodel_import->getFieldSets($this->params);
			
			$this->params['matches'] = $this->kamodel_import->getMatchesByPositions($sets, $this->params['columns'], $this->request->post);

			$this->kamodel_import->copyMatchesToSets($sets, $this->params['matches'], $this->data['columns']);

			$errors_found = false;			
			foreach ($sets['fields'] as $field) {
				if (!empty($field['required']) && empty($field['column'])) {
					$this->addTopMessage(sprintf($this->language->get('error_field_required'), $field['name']), 'E');
					$errors_found = true;
				}
			}
			
			if ($errors_found) {
				return $this->response->redirect($this->url->linka('extension/ka_product_import/import|step2'));
			}
			
			if ($this->request->post['mode'] == 'save_profile') {
			
				if (empty($this->request->post['profile_name'])) {
					$this->addTopMessage($this->language->get("Profile name is empty"), "E");
					
				} else {
				
					$profile_id = $this->kamodel_import_profiles->getProfileIdByName($this->request->post['profile_name']);
					
					$profile_id = $this->kamodel_import_profiles->setProfileParams($profile_id, $this->request->post['profile_name'], $this->params);
					if ($profile_id) {
						$this->params['profile_id'] = $profile_id;
						$this->addTopMessage($this->language->get("Profile has been updated su..."));
					} else {
						$this->addTopMessage($this->language->get("Profile has been added succ..."));
					}
				}
			
				return $this->response->redirect($this->url->linka('extension/ka_product_import/import|step2'));
			}
						
			return $this->response->redirect($this->url->linka('extension/ka_product_import/import|step3'));
		}

		if (!empty($this->params['tpl_product_id'])) {
			$this->data['is_def_fields_enabled'] = true;
		}

		// get fields in sets
		//
		$sets = $this->kamodel_import_product->getFieldSets($this->params);

		if (!empty($sets['options']) && count($sets['options']) > static::$max_visible_options) {
			if (empty($this->params['matches']['options'])) {
				$this->data['total_options_not_loaded'] = count($sets['options']);
				$sets['options'] = array();
				$this->data['max_visible_options'] = static::$max_visible_options;
			}
		}
		$replacement_columns = $this->kamodel_replacements->getReplacementColumns($this->params['ir_import_group_id'], $this->params['columns']);
		$this->data['replacement_columns'] = array_flip($replacement_columns);
		
		//
		// $matches - stores array of fields and assigned columns
		// $columns - list of columns in the file
		//
		if (empty($this->params['matches'])) {
			// do finding only once on step2 load
			$this->params['matches'] = $this->kamodel_import->getMatchesByColumnNames($sets, $this->data['columns']);
		}

		$this->kamodel_import->copyMatchesToSets($sets, $this->params['matches'], $this->data['columns']);
		
		$this->data['matches'] = $sets;

		if (!empty($sets['options'])) {
			if (count($sets['options']) < static::$max_expanded_options) {
				$this->data['all_options_expanded'] = true;
			}
		}
		
		$this->data['attribute_page_url'] = $this->url->linka('catalog/attribute');
		$this->data['filter_page_url']    = $this->url->linka('catalog/filter');
		$this->data['option_page_url']    = $this->url->linka('catalog/option');
    	$this->data['action']             = $this->url->linka('extension/ka_product_import/import|step2');
		$this->data['back_action']        = $this->url->linka('extension/ka_product_import/import');
		$this->data['params']             = $this->params;
		
		$profile = array();
		if ($this->params['profile_id']) {
			$profile = $this->kamodel_import_profiles->getProfile($this->params['profile_id']);
		}
		$this->data['profile'] = $profile;
		
		$this->data['filesize']           = $this->kaformat->convertToMegabyte(filesize($this->params['file']));
		$this->data['backup_link']        = $this->url->linka('tool/backup');
				
		if (!empty($this->session->data['hide_backup_warning'])) {
			$this->data['hide_backup_warning'] = true;
		}
		
		$this->document->setTitle($this->language->get('txt_form_page_title') . ': ' . $this->language->get('STEP 1 of 3'));
		
		$this->showPage('extension/ka_product_import/step2');
	}

	
	public function step3() { // step3

		$this->load->language('extension/ka_product_import/step3');
		
		$this->params['step'] = 3;

		$params = $this->params;

		if (!empty($params['tpl_product_id'])) {
			$params['default_values'] = $this->kamodel_import_product->getDefaultProduct($params['tpl_product_id']);
		}
		
		if (!$this->kamodel_import->initImport($params)) {
			$this->addTopMessage($this->kamodel_import->getLastError(), 'E');
			return $this->response->redirect($this->url->linka('extension/ka_product_import/import|step2'));
		}

		$this->data['action_done']        = $this->url->linka('extension/ka_product_import/import');
		$this->data['params']             = $this->params;
		$sec = $this->kamodel_import->getSecPerCycle();
		$this->data['update_interval']    = $sec.' - ' .($sec + 5);

		// format=raw&tmpl=component - these parameters are used for compatibility with Mojoshop
		//
 		$this->data['page_url'] = str_replace('&amp;', '&', $this->url->linka('extension/ka_product_import/import|stat', 'format=raw&tmpl=component&user_token=' . $this->session->data['user_token'], true));
		
		$this->document->setTitle($this->language->get('txt_form_page_title') . ': ' . $this->language->get('STEP 3 of 3'));
		$this->showPage('extension/ka_product_import/step3');
	}


	/*
		The function is called by ajax script and it outputs information in json format.

		json format:
			status - in progress, completed, error;
			...    - extra import parameters.
	*/
	public function stat() {

		if ($this->params['step'] != 3) {
			$this->addTopMessage($this->language->get('This script can be requeste...'), 'E');
			return $this->response->redirect($this->url->linka('extension/ka_product_import/import/step2', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->kamodel_import->processImport();

		$stat                  = $this->kamodel_import->getImportStat();
		$stat['messages']      = $this->kamodel_import->getImportMessages();
		$stat['time_passed']   = $this->kaformat->formatPassedTime(time() - $stat['started_at']);

		$stat['completion_at'] = sprintf("%.2f%%", $stat['stage_completion_at']);
		
		$stages             = $this->kamodel_import->getStages();
		$stage              = $stages[$stat['stage_id']];
		$stat['stage_info'] = $stage['title'] . ' (' . sprintf($this->language->get('%d of %d'), $stat['stage_id']+1, count($stages)) . ')';
		$stat['stage']      = $stage;
	
 		$this->response->setOutput(json_encode($stat));
	}


	/*
		ajax call from the second step
	*/
	public function get_simple_options() {
		$json = array();
		$data = array();

		// get columns
		//
		$this->data['columns'] = $this->params['columns'];
		array_unshift($this->data['columns'], '');
		$this->data['columns'] = array_unique($this->data['columns']);		

		// get all fields
		//
		$sets = $this->kamodel_import->getFieldSets($this->params);

		// get replacements
		//
		$this->data['replacement_columns'] = $this->kamodel_replacements->getReplacementColumns($this->params['ir_import_group_id'], $this->params['columns']);
		
		if (!empty($this->params['matches'])) {
			$this->kamodel_import->copyMatchesToSets($sets, $this->params['matches'], $this->data['columns']);
		}

		if (empty($this->params['profile_id'])) {
			$this->kamodel_import->getMatchesByColumnNames($sets, $this->data['columns']);
		}
		
		$this->data['matches'] = $sets;
		
		$html = $this->load->view('extension/ka_product_import/step2/simple_options', $this->data);
		
		$json = array(
			'html'   => $html
		);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	/*
		Get import parameters
	*/
	protected function getImportParameters() {
	
		if (empty($this->params) || ($this->request->server['REQUEST_METHOD'] == 'GET' && empty($this->session->data['save_params']))) {
			$params = $this->kamodel_import->getDefaultImportParams();
	 	} else {
	 		$params = $this->params;
	 	}
	 	
		$params['iconv_exists']       = function_exists('iconv');
		$params['filter_exists']      = in_array('convert.iconv.*', stream_get_filters());
		$params['image_urls_allowed'] = false;
		
		if (ini_get('allow_url_fopen') || function_exists('curl_version')) {
			$params['image_urls_allowed'] = true;
		}
	 	
	 	return $params;
	}
	
}