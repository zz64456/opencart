<?php
/*
	This file was inherited by kamod.
	More information can be found at https://www.ka-station.com/kamod
	
	Original file: extension/ka_extensions/kamod/system/library/url.php
*/

namespace extension\ka_extensions\library;

use \extension\ka_extensions\KaGlobal;

require_once(__DIR__ . '/url.1.kamod.php');

class Url extends \Opencart\System\Library\Url_kamod  {

	public function linka(string $route, $args = null, bool $js = false): string {

		if (version_compare(VERSION, '4.0.2.0', '>=')) {
			$route = str_replace('|', '.', $route);
		}
	
		$session = KaGlobal::getRegistry()->get('session');
		if (KaGlobal::isAdminArea() && !empty($session->data['user_token'])) {
			if (is_null($args)) {
				$args = ['user_token' => $session->data['user_token']];
			} else {				
				if (is_array($args)) {
					$args = array_merge($args, ['user_token' => $session->data['user_token']]);
				} else {
					$args = $args . '&user_token=' . $session->data['user_token'];
				}
			}
		}
	
		if (is_null($args)) {
			$args = '';
		}
		
		return parent::link($route, $args, $js);
	}
}