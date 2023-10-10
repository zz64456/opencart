<?php
namespace extension\ka_extensions;

class ModelDesignTheme extends \Opencart\Admin\Model\Design\Theme {

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