<?php
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
	
	This file replaced standard Opencart autoloader.
	
	It installs our kamodmanager as a handler of class loading that helps to overwrite requires classes
	with our modified kamod copies.
*/

namespace extension\ka_extensions;

include_once(__DIR__ . '/kamodmanager.php');

class Autoloader {

	protected $parent_autoloader;
	protected $kamod_manager;

	public function __construct($org_autoloader) {

		$this->kamod_manager = KamodManager::getInstance();

		if (defined('KAMOD_DEBUG')) {
		
			$this->kamod_manager->rebuildKamodCache();
			
		} elseif (!$this->kamod_manager->isKamodCacheValid()) {
	
			// the cache directory may contain diferent files: 
			// locked.kamod  - another process is rebuilding the cache
			// valid.kamod   - the cache is valid and up to date
			// invalid.kamod - just for user's information, do not rely on it
			//
			$this->kamod_manager->rebuildKamodCache();
			
			if (!$this->kamod_manager->isKamodCacheValid()) {
				throw new \Exception("Kamod cache is not valid");
			}
		}
		
		$this->parent_autoloader = $org_autoloader;
		
		spl_autoload_register([$this, 'load'], true, true);
	}

	
	public function load(string $class): bool {

		// we try to load the class from ka_cache directory
		//
		if ($this->kamod_manager->loadClass($class)) {
			return true;
		}

		// we load extension classes directly when their namespace starts with 'extension'
		//
		if (substr($class, 0, 10) == 'extension\\') {

			$pos = strpos($class, '\\', 10);
			if ($pos === false) {
				// if someone places a class into extension directory root let's Opencart handle it
				return false;
			}
			$extension_code  = substr($class, 0, $pos);
			$extension_class = substr($class, $pos + 1);
			
			$class_path = strtolower($extension_code . '\\system\\' . $extension_class);
			
			$file = DIR_OPENCART . str_replace('\\', '/', $class_path) . '.php';
			if (file_exists($file)) {
				include_once($file);
				if (class_exists($class) || trait_exists($class)) {
					return true;
				}
			}
		}			
		
		return $this->parent_autoloader->load($class);
	}

	
	function __call($name, $args) {
		return call_user_func_array(array($this->parent_autoloader, $name), $args);
	}
	
	
	public function ka_loadAreaClass($class) {
	
		if (APPLICATION == 'Admin') {
			$file = 'admin\\' . $class . '.php';
		} else {
			$file = 'catalog\\' . $class . '.php';
		}
		
		if (!$this->kamod_manager->loadCacheFile($file)) {
			return false;
		}

		if (!class_exists($class)) {
			return false;
		}
		
		return true;
	}
}