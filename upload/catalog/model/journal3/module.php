<?php

class ModelJournal3Module extends Model {

	public function get($module_id, $module_type) {
		static $results;

		if ($results === null) {
			$query = $this->db->query("SELECT * FROM `{$this->journal3_db->escape(DB_PREFIX . 'journal3_module')}`");

			$results = array();

			foreach ($query->rows as $row) {
				$results[$row['module_type']][$row['module_id']] = $row;
			}
		}

		$module_data = $results[$module_type][$module_id]['module_data'] ?? '';

		return $module_data ? $this->journal3_db->decode($module_data, true) : array();
	}

	public function getByType($module_type) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "journal3_module` WHERE `module_type` = '" . $this->journal3_db->escape($module_type) . "'");

		$results = array();

		foreach ($query->rows as $row) {
			$results[$row['module_id']] = $this->journal3_db->decode($row['module_data'], true);
		}

		return $results;
	}

}

class_alias('ModelJournal3Module', '\Opencart\Catalog\Model\Journal3\Module');
