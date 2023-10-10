<?php
/*
	$Project: CSV Product Import $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 6.0.0.2 $ ($Revision: 581 $)
	
	This file adds the 'Ka Extensions' menu item to the main menu.
	
*/
namespace extension\ka_product_import;

use extension\ka_extensions\Arrays;

class ControllerCommonColumnLeft extends \Opencart\Admin\Controller\Common\ColumnLeft {

	use \extension\ka_extensions\TraitController;

	public function index(): string {

		if (!$this->user->hasPermission('access', 'extension/ka_product_import/extension')) {
			return parent::index();
		}

		$this->load->disableRender();
		parent::index();
		$this->load->enableRender();
		
		$data = $this->getRenderData();
		$template = $this->getRenderTemplate();		
		
		$data['menus'] = $this->injectKaProductImportMenu($data['menus']);
		
		return $this->load->view($template, $data);
	}
	
	
	protected function injectKaProductImportMenu($menus) {

		foreach ($menus as $mk => $menu) {

			if ($menu['id'] != 'menu-system') {
				continue;
			}

			$this->load->language('extension/ka_product_import/menu');

			$new_item = array(
				'name'	   => $this->language->get('CSV Product Import'),
				'href'     => $this->url->linka('extension/ka_product_import/import'),
				'children' => array()		
			);					
			$ka_csv_import_additions[] = array(
				'name'	   => $this->language->get('Import Groups'),
				'href'     => $this->url->linka('extension/ka_product_import/import_group'),
				'children' => array()		
			);
			$ka_csv_import_additions[] = array(
				'name'	   => $this->language->get('Import Replacements'),
				'href'     => $this->url->linka('extension/ka_product_import/replacements'),
				'children' => array()		
			);
			$ka_csv_import_additions[] = array(
				'name'	   => $this->language->get('Import Skip Rules'),
				'href'     => $this->url->linka('extension/ka_product_import/import_skip_rule'),
				'children' => array()		
			);
			$ka_csv_import_additions[] = array(
				'name'	   => $this->language->get('Import Price Rules'),
				'href'     => $this->url->linka('extension/ka_product_import/import_price_rule'),
				'children' => array()
			);
			$new_item2 = array(
				'name'	   => $this->language->get('CSV Product Extra'),
				'href'     => '',
				'children' => $ka_csv_import_additions	
			);

			// try to add the new menu items after the 'Settings' menu item
			foreach ($menu['children'] as $ck => $cv) {

				if (!strpos($cv['href'], 'setting/store')) {
					continue;
				}

				$menus[$mk]['children'] = Arrays::insertAfterKey($menus[$mk]['children'], $ck, [$new_item, $new_item2]);
				
				break 2;
			}
			
			// we get here if the 'Setting' menu item was not found. In that case we add ourselves at the top
			$menus[$mk]['children'] = array_merge([$new_item, $new_item2], $menus[$mk]['children']);
		}

		return $menus;
	}
}