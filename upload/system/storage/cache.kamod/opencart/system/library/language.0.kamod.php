<?php
/*
	This file was inherited by kamod.
	More information can be found at https://www.ka-station.com/kamod
	
	Original file: extension/ka_extensions/kamod/system/library/language.php
*/
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
	
	Added a few improvements
	
*/
namespace extension\ka_extensions\system\library;

require_once(__DIR__ . '/language.1.kamod.php');

class Language extends \Opencart\System\Library\Language_kamod  {

	/*
		This feature is used in twig template to check if the variable is defined or not. In twig, you can do
		this:
		{% if has_t('text_home') %}{{ text_home }}{% if %}
	*/
	public function has($text) {
		return isset($this->data[$text]);
	}

	
	public function load(string $filename, string $prefix = '', string $code = ''): array {
	
		parent::load($filename, $prefix, $code);

		if (!$code) {
			$code = $this->code;
		}

		// we try to load English interface if the language file was not found for the current interface
		// any translation is better than seeing an empty page
		//
		if (empty($this->cache[$code][$filename])) {
			if (substr_compare('extension/',$filename, 0, 10) === 0) {
				$pos      = strpos($filename, '/', 10);
				$ext_dir  = substr($filename, 0, $pos);
				$lang_dir = substr($filename, $pos);
				if (defined('DIR_CATALOG')) {
					$file = DIR_OPENCART . $ext_dir . '/admin/language/en-gb' . $lang_dir . '.php';
				} else {
					$file = DIR_OPENCART . $ext_dir . '/catalog/language/en-gb' . $lang_dir . '.php';
				}
			} else {
				$file = DIR_APPLICATION . 'language/en-gb/' . $filename . '.php';
			}
			if (file_exists($file)) {
				$_ = [];
				include($file);
				
				if (!empty($_)) {
				
					if (empty($prefix)) {
						$this->cache[$code][$filename] = $_;

					} else {
						// prefixes are used on the extensions page where Opencart loads variables from several 
						// extensions and they have same names. So it adds prefixes to distinguish them
						//
						$prefix .= '_';
						$keys = array_keys($_);
						$_ = array_combine(
							array_map(
								function($k) use ($prefix) { 
									return $prefix . $k; 
								}, 
								$keys 
							),
							$_							
						);
					}
				}
				
				$this->data = array_merge($this->data, $_);
			}
		}
		
		return $this->data;
	}
}