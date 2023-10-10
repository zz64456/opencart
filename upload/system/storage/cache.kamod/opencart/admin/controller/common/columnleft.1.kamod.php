<?php
/*
	This file was inherited by kamod.
	More information can be found at https://www.ka-station.com/kamod
	
	Original file: extension/ka_extensions/kamod/admin/controller/common/column_left.php
*/
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
	
	This file adds the 'Ka Extensions' menu item to the main menu.
	
*/
namespace extension\ka_extensions\common;

use \extension\ka_extensions\Arrays;

require_once(__DIR__ . '/columnleft.2.kamod.php');

class ControllerColumnLeft extends \Opencart\Admin\Controller\Common\ColumnLeft_kamod  {

	use \extension\ka_extensions\TraitController;

	public function index(): string {
	
		$this->load->disableRender();
		parent::index();
		$this->load->enableRender();
		
		$data = $this->getRenderData();
		$template = $this->getRenderTemplate();		

		$data['menus'] = $this->injectKaExtensionsMenu($data['menus']);
		
		$this->checkVendorPatch();
		
		return $this->load->view($template, $data);
	}
	
	
	protected function injectKaExtensionsMenu($menus) {

		foreach ($menus as $mk => $menu) {

			if ($menu['id'] != 'menu-extension') {
				continue;
			}		

			foreach ($menu['children'] as $ck => $cv) {
			
				if (!strpos($cv['href'], 'marketplace/extension')) {
					continue;
				}

				$new_item = array(
					'name' => $this->language->get('Ka Extensions'),
					'href' => $this->url->link('extension/ka_extensions/extensions', 'user_token=' . $this->session->data['user_token']),
				);
				
				$menus[$mk]['children'] = Arrays::insertAfterKey($menus[$mk]['children'], $ck, [$new_item]);
				
				break 2;
			}
		}

		return $menus;
	}

	
	protected function checkVendorPatch() {
	
		$this->load->model('setting/setting');
	
		$kamod_install_time = filemtime(DIR_EXTENSION . 'ka_extensions/install.json');
		$patched_time = $this->model_setting_setting->getValue('kamod_vendor_patched_time');
		
		if (empty($patched_time) || $patched_time < $kamod_install_time) {
			$this->load->controller('marketplace/installer|vendor');
			
			// reset controller output
			$this->response->setOutput('');
			$this->response->setHeaders(array());
		}
	}
	
}