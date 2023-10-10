<?php
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
*/

namespace extension\ka_extensions;

include_once(__DIR__ . '/kamodbuilder.php');

class KamodManager {

	public const ACTIVE_KAMOD_FILE = 'active.kamod';

	const KAMOD_CACHE_DIR     = 'cache.kamod';
	const KAMOD_TEMPLATES_DIR = 'templates';
	const THEME_CACHE_DIR     = 'theme.kamod';

	protected $kamod_builder;
	protected $ka_cache_dir;
	
	protected $theme_cache_dir;
	protected $catalog_dir;
	protected $system_dir;
	protected $admin_dir;
	
	// link to the kamod manager object
	protected static $instance;
	
	
	public static function getKamodCacheDir() {
		return DIR_STORAGE . static::KAMOD_CACHE_DIR . '/';
	}
	
	
	public static function getKamodTemplatesDir($store_id = null) {
	
		if (is_null($store_id)) {
			return static::getKamodCacheDir() . static::KAMOD_TEMPLATES_DIR . '.default/';
		}
		
		return static::getKamodCacheDir() . static::KAMOD_TEMPLATES_DIR . '.' . $store_id . '/';
	}
	
	
	//ok
	protected function __construct() {
		$root_dir    = DIR_OPENCART;

		$this->theme_cache_dir = DIR_STORAGE . static::THEME_CACHE_DIR . '/';
		$this->ka_cache_dir    = DIR_STORAGE . static::KAMOD_CACHE_DIR . '/';

		$this->kamod_builder = new KamodBuilder($root_dir, 
			$this->ka_cache_dir, 
			static::KAMOD_TEMPLATES_DIR,
			$this->theme_cache_dir
		);
		if (empty($this->kamod_builder)) {
			throw new \Exception("Kamod builder cannot be created");
		}

		$this->initMainStoreDirectoryNames();
	}
	
	public static function getInstance() {
		
		if (!is_null(static::$instance)) {
			return static::$instance;
		}
		
		static::$instance = new KamodManager();
		
		return static::$instance;
	}
	
	public function isKamodCacheValid() {
	
		$is_valid = $this->kamod_builder->isCacheValid();
		
		return $is_valid;
	}

	
	public function isKamodCacheEmpty() {

		$is_empty = $this->kamod_builder->isCacheEmpty();
		
		return $is_empty;
	}
	
	
	public function markKamodCacheInvalid() {
		$this->kamod_builder->markCacheInvalid();
	}
	
	//ok
	protected function initMainStoreDirectoryNames() {

		$this->catalog_dir = 'catalog';
		$this->system_dir  = 'system';
	
		if (APPLICATION == 'Admin') {
			$this->admin_dir = basename(DIR_APPLICATION);
			
		} else {
			$config_file = DIR_CONFIG . 'kamod.php';
			if (!file_exists($config_file)) {
				throw new \Exception("The kamod config file was not found: $config_file");
			}
			
			$_ = [];
			include($config_file);

			if (empty($_['admin_dir'])) {
				throw new \Exception("The admin directory was not found in config file ($config_file)");
			}
			
			$this->admin_dir = $_['admin_dir'];
		}

		$service_dirs = array(
			'admin'   => $this->admin_dir,
			'catalog' => $this->catalog_dir,
			'system'  => $this->system_dir,
		);
		
		$this->kamod_builder->setServiceDirs($service_dirs);
	}
	
	
	/*
		Return all possible directories of the module
		
		$module_code - directory name inside the 'extension' directory
		
	*/
	protected function getModuleDirs($extension_code) {

		$dirs = array();
		$extension_dir = basename(DIR_EXTENSION);
		$dirs[] = $extension_dir . '/' . $extension_code . '/kamod';

		return $dirs;
	}

	
	protected function isExtensionActive($extension_code) {
	
		$active_marker = DIR_EXTENSION . $extension_code . '/' . static::ACTIVE_KAMOD_FILE;
		
		if (file_exists($active_marker)) {
			return true;
		}
		
		return false;
	}


	/*
	*/
	protected function getAllModuleDirs() {
	
		$kamod_dirs = array();
	
		$extension_dirs = glob(DIR_EXTENSION . '*', GLOB_ONLYDIR);
		
		if (empty($extension_dirs)) {
			return $kamod_dirs;
		}

		foreach ($extension_dirs as $ed) {

			if (!file_exists($ed . '/' . 'kamod')) {
				continue;
			}
		
			// get the extension name
			//
			$extension_code = basename($ed);
			
			// check if the module is installed
			//
			if (!$this->isExtensionActive($extension_code)) {
				continue;
			}

			// get the module directories
			//
			$dirs = $this->getModuleDirs($extension_code);
			
			$kamod_dirs = array_merge($kamod_dirs, $dirs);
		}
		
		return $kamod_dirs;
	}
	
	
	/*
		Throws an exception on failure		
	*/
	public function rebuildKamodCache() {

		$module_dirs = $this->getAllModuleDirs();

		$this->kamod_builder->setModuleDirs($module_dirs);

		$this->kamod_builder->buildCache();
		
		// save config file
		//
		if (APPLICATION == 'Admin') {
		
			$admin_dir = $this->admin_dir;
		
			$contents = <<<CFGTXT
<?php			
/*
	This file is regenerated automatically on kamod rebuild. Do not set any configuration parameters here manually.
	More information on kamod can be found at https://www.ka-station.com/kamod
*/
\$_['admin_dir'] = '$admin_dir';
CFGTXT;
		
			file_put_contents(DIR_CONFIG . 'kamod.php', $contents);
		}
	}
	
	
	public function loadClass($class) {

		$file = strtolower(str_replace('\\', '/', $class)) . '.php';

		if (!$this->loadCacheFile($file)) {
			return false;
		}

		if (class_exists($class)) {
			return true;
		}
		
		return false;
	}

	
	public function loadCacheFile($file) {

		$full_file = $this->ka_cache_dir . $file;
	
		if (!file_exists($full_file))
			return false;
	
		include_once($full_file);
			
		return true;		
	}
	
	
	public function emptyThemeCache() {

		$theme_dir = $this->theme_cache_dir;
		Directory::clearDirectory($theme_dir);
	
		$this->kamod_builder->log("Theme cache was emptied");

		$contents = <<<TXT
The directory contains copies of theme files modified by administrator. They are used for building
kamod cache on top of them. Do not erase this directory otherwise user's changes may not show in template
files.

The directory number specifies store id. 0 - default store.

More information on kamod can be found at https://www.ka-station.com/kamod		
TXT;

		$file = $theme_dir . 'readme.txt';
		
		Directory::checkDirectory($file);
		file_put_contents($file, $contents);
	}
	
	
	public function storeThemeFile($store_id, $route, $code) {

		$file = $this->theme_cache_dir . $store_id . '/catalog/view/template/' . $route . '.twig';

		Directory::checkDirectory($file);		
		file_put_contents($file, $code);
		
		$this->kamod_builder->log("Saved a theme cache file: " . $file);
	}
	
	
	public function rebuildTwigCache() {
		$this->markKamodCacheInvalid();
	}	
	
	public function getLastErrorsTotal() {
	
		$file = DIR_LOGS . KamodBuilder::LOG_ERRORS_FILENAME;
		
		if (!file_exists($file)) {
			return 0;
		}
		
		$rows = file($file);
		
		return count($rows);
	}
	
}