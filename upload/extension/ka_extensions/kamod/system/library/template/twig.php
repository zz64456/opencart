<?php
namespace extension\ka_extensions\library\template;

use \extension\ka_extensions\KaGlobal;
use \extension\ka_extensions\KamodManager;

class Twig extends \Opencart\System\Library\Template\Twig {

	protected $kamod_cache_loader;
	protected $store_cache_loader;
	
	protected $is_chain_loader_set = false;

	public function render(string $filename, array $data = [], string $code = ''): string {
	
		if (!$this->is_chain_loader_set) {
			$this->setChainLoader();
			$this->is_chain_loader_set = true;
		}			

		$result = '';
		try {
			$result = parent::render($filename, $data, $code);
		} catch (\Throwable $e) {
			if (defined('KAMOD_DEBUG')) {
				echo $e->getMessage();
			}
			KaGlobal::getRegistry()->get('log')->write('Render error ' . $e->getMessage());
		}
		
		return $result;
	}

	protected function setChainLoader() {
	
		$relative_paths = array();
		
		$paths = array();
		
		// define shortcuts
		//
		$admin_dirname = KaGlobal::getAdminDirName();		
		if (KaGlobal::isAdminArea()) {			
			if (file_exists(DIR_OPENCART . 'extension/ka_extensions/admin/view/template')) {
				$paths['ka_common']  =  'extension/ka_extensions/admin/view/template/common';
			}
			
		} else {
			if (file_exists(DIR_OPENCART . 'extension/ka_extensions/catalog/view/template')) {
				$paths['ka_common'] = 'extension/ka_extensions/catalog/view/template';
			}
		}
		if (file_exists(DIR_OPENCART . 'extension/ka_extensions/shared/view/template')) {
			$paths['ka_shared']  = 'extension/ka_extensions/shared/view/template';
		}

		$paths['ka_admin']   = $admin_dirname . '/view/template';
		$paths['ka_catalog'] = 'catalog/view/template';
		
		// define loaders
		//
		$chain_loader = new \Twig\Loader\ChainLoader();
		
		$oc_loader_dir    = DIR_OPENCART;
		
		$store_id = (int)KaGlobal::getRegistry()->get('config')->get('config_store_id');

		$kamod_cache_dir = KamodManager::getKamodTemplatesDir();
		$store_cache_dir = KamodManager::getKamodTemplatesDir($store_id);
		if (!is_dir($store_cache_dir)) {
			mkdir($store_cache_dir, 0777, true);
		}
		
		$this->kamod_cache_loader = new \Twig\Loader\FilesystemLoader('/', $kamod_cache_dir);
		$this->store_cache_loader = new \Twig\Loader\FilesystemLoader('/', $store_cache_dir);
		
		$is_store_cache_found = false;
		foreach($paths as $k => $v) {
			if (is_dir($oc_loader_dir . $v)) {
				$this->loader->addPath($v, $k);
			}
			if (is_dir($kamod_cache_dir . $v)) {
				$this->kamod_cache_loader->addPath($v, $k);
			}
			if (is_dir($store_cache_dir . $v)) {
				$is_store_cache_found = true;
				$this->store_cache_loader->addPath($v, $k);
			}
		}

		// store cache loader (these templates were modified by the admin for the specific store via 'Theme editor'
		if ($is_store_cache_found) {
			$chain_loader->addLoader($this->store_cache_loader);
		}
		
		// kamod cache is a default template for stores
		$chain_loader->addLoader($this->kamod_cache_loader);
		
		// opencart loader is a a standard template directory
		$chain_loader->addLoader($this->loader);
		
		$this->loader = $chain_loader;
	}
	
	protected function extendTwig($twig) {
		$twig->addExtension(new \extension\ka_extensions\TwigExtension());
	}
}