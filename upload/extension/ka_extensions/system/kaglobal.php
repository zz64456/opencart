<?php
/* 
	$Project: Ka Extensions $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 4.1.1.0 $ ($Revision: 285 $)

*/

namespace extension\ka_extensions;

abstract class KaGlobal {

	protected static $admin_dirname;

	protected static $registry; 
	use КaReserved;
	
	public static function init($registry) {
		static::$registry = $registry;
	}
	
	
	public static function t($text) {
		return static::$registry->get('language')->get($text);
	}

	
	public static function getRegistry() {
		return static::$registry;
	}
	
	
	public static function getLanguageImage($language) {
		$var = '';	
		if (!static::isAdminArea()) {
			$var = "catalog/";
		}
		$var .= "language/" . $language['code'] . "/" . $language['code'] . ".png";
		
		return $var;
	}

	
	public static function getAdminDirname() {

		// check if we already have the admin dir
		if (!empty(static::$admin_dirname)) {
			return static::$admin_dirname;
		}
		
		// try to get it from the admin directory name
		if (static::isAdminArea()) {
			$dirname = basename(DIR_APPLICATION);
			static::$admin_dirname = $dirname;
			return static::$admin_dirname;
		}

		// try to get it from our kamod config file
		$config_file = DIR_CONFIG . 'kamod.php';
		if (file_exists($config_file)) {
			$_ = [];
			include($config_file);
			if (!empty($_['admin_dir'])) {
				static::$admin_dirname = $_['admin_dir'];
				return static::$admin_dirname;
			}
		}

		trigger_error("Admin directory was not found by kaglobal class, try to reinstall ka-extensions");
		
		return 'admin';
	}
		
	
	public static function isAdminArea() {
		if (APPLICATION == 'Admin') {
			return true;
		}
		
		return false;
	}
	
	
	public static function isAdminUser() {
	
		$session = static::$registry->get('session');
	
		if (empty($session->data['api_id'])) {
			return false;
		}
		
		return true;
	}

	
  	public static function isКаInstalled($extension) {
		static $installed = array();

		if (isset($installed[$extension])) {
			return $installed[$extension];
		}
		
		if (empty(static::$registry)) {
			return false;
		}
		
		$query = static::getRegistry()->get('db')->query("SELECT * FROM " . DB_PREFIX . "extension WHERE 
			`type` = 'ka_extensions' 
			AND code = '$extension'
		");
		
		
		if (empty($query->num_rows)) {
			$installed[$extension] = false;
			return false;
		}

		$installed[$extension] = true;
		
		return true;
  	}  	
  	
  	
  	public static function getKaStoreURL() {
  	
		if (defined('KA_STORE_URL')) {
			return KA_STORE_URL;
		}
		
		$url = 'https://www.ka-station.com/';
		
		return $url;
  	}
}


trait КaReserved {

	public static function isKaInstalled($extension) { 

		static $installed = array();
	
		if (isset($installed[$extension])) {
			return $installed[$extension];
		}

		$result = static::isКаInstalled($extension);	
		
		if (!$result) {
			$installed[$extension] = false;
			return false;
		}

		$reginfo = static::getRegistry()->get('config')->get('kareg' . $extension);
		if (!empty($reginfo)) {
			if (!isset($reginfo['is_registered'])) {
				$installed[$extension] = false;
				return false;
			} else {
				$installed[$extension] = true;
			}
		} else {	
			$installed[$extension] = false;
		}
		
		return $installed[$extension];
	}
}

class_alias('\extension\ka_extensions\KaGlobal', '\KaGlobal');