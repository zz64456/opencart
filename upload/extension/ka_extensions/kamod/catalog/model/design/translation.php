<?php
/*
	$Project$
	$Author$

	$Version$ ($Revision$)

	The translations are called heavily by Opencart. We add a cache here to prevent requesting them from DB
	directly everytime.

*/

namespace extension\ka_extensions\design;

class ModelTranslation extends \Opencart\Catalog\Model\Design\Translation {

	public function getTranslations(string $route): array {
		static $cache = array();
		
		if (isset($cache[$route])) {
			return $cache[$route];
		}
		
		$cache[$route] = parent::getTranslations($route);

		return $cache[$route];
	}
}
