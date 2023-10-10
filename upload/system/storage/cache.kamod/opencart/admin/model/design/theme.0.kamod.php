<?php
/*
	This file was inherited by kamod.
	More information can be found at https://www.ka-station.com/kamod
	
	Original file: extension/ka_extensions/kamod/admin/model/design/theme.php
*/
namespace extension\ka_extensions;

require_once(__DIR__ . '/theme.1.kamod.php');

class ModelDesignTheme extends \Opencart\Admin\Model\Design\Theme_kamod  {

	public function editTheme(int $store_id, string $route, string $code): void {
	
		parent::editTheme($store_id, $route, $code);
		
		$kamodel_kamod = $this->load->kamodel('extension/ka_extensions/kamod');
		$kamodel_kamod->rebuildThemeCache();
	}

	public function deleteTheme(int $theme_id): void {
	
		parent::deleteTheme($theme_id);
		
		$kamodel_kamod = $this->load->kamodel('extension/ka_extensions/kamod');
		$kamodel_kamod->rebuildThemeCache();
	}

}