<?php

namespace extension\ka_extensions\library;

use \extension\ka_extensions\KaGlobal;

class Url extends \Opencart\System\Library\Url {

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