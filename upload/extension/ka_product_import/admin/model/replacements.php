<?php 
/*
	$Project: CSV Product Import $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 6.0.0.2 $ ($Revision: 572 $)
*/

namespace extension\ka_product_import;

class ModelReplacements extends \extension\ka_extensions\Model {

	protected $replacements_in_cache = 30;

	public function saveReplacement($data) {

		if (empty($data['import_replacement_id'])) {
			if (empty($data['column_name'])) {
				return false;
			}
		
			$this->db->query("INSERT INTO " . DB_PREFIX . "ka_import_replacements SET 
				import_group_id = '" . (int)$data['import_group_id'] . "',
				column_name = '" . $this->db->escape($data['column_name']) . "',
				old_value = '" . $this->db->escape($data['old_value']) . "',
				new_value = '" . $this->db->escape($data['new_value']) . "'				
			");
			$import_replacement_id = $this->db->getLastId();
		} else {
			$import_replacement_id = (int)$data['import_replacement_id'];

			$this->db->query("UPDATE " . DB_PREFIX . "ka_import_replacements SET
				import_group_id = '" . (int)$data['import_group_id'] . "',
				column_name = '" . $this->db->escape($data['column_name']) . "',
				old_value = '" . $this->db->escape($data['old_value']) . "',
				new_value = '" . $this->db->escape($data['new_value']) . "'				
				WHERE import_replacement_id = '" . $import_replacement_id . "'
			");
		}
		
		return $import_replacement_id;
	}
	
	
	public function deleteReplacement($import_replacement_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "ka_import_replacements 
			WHERE import_replacement_id = '" . (int)$import_replacement_id . "'
		");
	}

	
	public function getReplacement($import_replacement_id) {
	
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ka_import_replacements 
			WHERE import_replacement_id = '" . (int)$import_replacement_id . "'"
		);
		
		return $query->row;
	}

	protected function getRecords($data) {

		if (empty($data['fields'])) {
			trigger_error("No fields data in getRecords() function");
			return false;
		}
	
		$sql = "SELECT " . $data['fields'] . " FROM " . DB_PREFIX . "ka_import_replacements t1";

		$where = array();
		if (!empty($data['filter_text'])) {
			$where[] = "column_name LIKE '%" . $this->db->escape($data['filter_text']) . "%'";
		}

		if (!empty($data['filter_import_group_id'])) {
			$where[] = "import_group_id = '" . (int)$data['filter_import_group_id'] . "'";
		}
		
		if (!empty($where)) {
			$sql .= " WHERE " . implode(" AND ", $where);
		}
		
		if (!empty($data['sort'])) {

			if (!in_array($data['sort'], array('column_name'))) {
				$data['sort'] = 'column_name';
			}			
			$sql .= " ORDER BY " . $data['sort'];

			if (!in_array($data['order'], array('ASC', 'DESC'))) {
				$data['order'] = 'ASC';
			}					
			if (!empty($data['order'])) {
				$sql .= ' ' . $data['order'];
			}
		}
		
		if (isset($data['start']) || isset($data['limit'])) {
			if (empty($data['start']) || $data['start'] < 0) {
				$data['start'] = 0;
			}				

			if (empty($data['limit']) || $data['limit'] < 1) {
				$data['limit'] = 20;
			}
		
			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];

		}

		$query = $this->db->query($sql);
		
		return $query;
	}
	
	
	public function getReplacements($data = array()) {
	
		$data['fields'] = '*';

		$qry = $this->getRecords($data);
		
		return $qry->rows;
	}
	
	
	public function getReplacementsTotal($data = array()) {
	
      	$data['fields'] = 'COUNT(*) AS total';
      	
      	unset($data['start'], $data['sort']);
      	
		$qry = $this->getRecords($data);
		if (empty($qry->row)) {
			return 0;
		}
		
		return $qry->row['total'];
	}

	
	public function isInstalled() {

		$qry = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "ka_import_replacements'");
		
		if (empty($qry->rows)) {
			return false;
		}
		
		return true;
	}
	
	/*
		RETURNS:
			- array of replacement data
	
	*/
	public function initReplacementsCache($import_group_id, $replacement_columns) {
		
		$replacements = array();
	
		if (empty($replacement_columns)) {
			return $replacements;
		}
		
		foreach ($replacement_columns as $rck => $rcv) {
			$qry = $this->db->query("SELECT * FROM " . DB_PREFIX . "ka_import_replacements WHERE
				import_group_id = '" .(int)$import_group_id . "'
				AND column_name = '" . $this->db->escape($rck) . "' LIMIT " . $this->replacements_in_cache . "
			");
			
			if (empty($qry->rows)) {
				continue;
			}

			$replacements[$rck] = array(
				'column'     => $rcv,
				'cache_only' => ($qry->num_rows < $this->replacements_in_cache),
				'cache'      => array()
			);
			foreach ($qry->rows as $irk => $irv) {
				$replacements[$rck]['cache'][$irv['old_value']] = $irv['new_value'];
			}
		}
		
		return $replacements;
	}
	
	
	/*
		Returns column names having replacement data in the database.
		
		PARAMS:
			columns_in_use - array with all columns mapped in the file.
				<poistion> => <name>
			
		RETURN:
			array with pairs:
			<column name> => <column position>
	*/
	public function getReplacementColumns($import_group_id, $columns_in_use) {

		$qry = $this->db->query("SELECT DISTINCT column_name FROM " . DB_PREFIX . "ka_import_replacements
			WHERE import_group_id = '" . (int)$import_group_id . "'
		");
		if (empty($qry->rows)) {
			return array();
		}
		
		$replacement_columns = array();
		
		$column_positions = array_flip($columns_in_use);
		
		foreach ($qry->rows as $rk => $rv) {
			if (in_array($rv['column_name'], $columns_in_use)) {
				$replacement_columns[$rv['column_name']] = $column_positions[$rv['column_name']];
			}
		}
	
		return $replacement_columns;
	}
}