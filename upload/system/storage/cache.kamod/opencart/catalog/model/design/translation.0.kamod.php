<?php
/*
	This file was inherited by kamod.
	More information can be found at https://www.ka-station.com/kamod
	
	Original file: extension/ka_extensions/kamod/catalog/model/design/translation.php
*/
/*
	$Project$
	$Author$

	$Version$ ($Revision$)

	The translations are called heavily by Opencart. We add a cache here to prevent requesting them from DB
	directly everytime.

*/

namespace extension\ka_extensions\design;

require_once(__DIR__ . '/translation.1.kamod.php');

class ModelTranslation extends \Opencart\Catalog\Model\Design\Translation_kamod  {

	public function getTranslations(string $route): array {
		static $cache = array();
		
		if (isset($cache[$route])) {
			return $cache[$route];
		}
		
		$cache[$route] = parent::getTranslations($route);

		return $cache[$route];
	}
}
