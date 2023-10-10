<?php
/*
	This file was inherited by kamod.
	More information can be found at https://www.ka-station.com/kamod
	
	Original file: catalog/model/design/translation.php
*/
namespace Opencart\Catalog\Model\Design;
/**
 * Class Translation
 *
 * @package Opencart\Catalog\Model\Design
 */
class Translation_kamod extends \Opencart\System\Engine\Model  {
	/**
	 * @param string $route
	 *
	 * @return array
	 */
	public function getTranslations(string $route): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "translation` WHERE `store_id` = '" . (int)$this->config->get('config_store_id') . "' AND `language_id` = '" . (int)$this->config->get('config_language_id') . "' AND `route` = '" . $this->db->escape($route) . "'");

		return $query->rows;
	}
}
