<?php
/*
	This file was inherited by kamod.
	More information can be found at https://www.ka-station.com/kamod
	
	Original file: catalog/model/design/theme.php
*/
namespace Opencart\Catalog\Model\Design;
/**
 * Class Theme
 *
 * @package Opencart\Catalog\Model\Design
 */
class Theme_kamod extends \Opencart\System\Engine\Model  {
	/**
	 * @param string $route
	 *
	 * @return array
	 */
	public function getTheme(string $route): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "theme` WHERE `store_id` = '" . (int)$this->config->get('config_store_id') . "' AND `route` = '" . $this->db->escape($route) . "'");

		return $query->row;
	}
}