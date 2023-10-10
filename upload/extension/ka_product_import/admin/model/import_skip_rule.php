<?php 
/*
	$Project: CSV Product Import $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 6.0.0.2 $ ($Revision: 572 $)
*/

namespace extension\ka_product_import;

class ModelImportSkipRule extends \extension\ka_extensions\Model {

	public function isInstalled() {

		$qry = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "ka_import_skip_rules'");
		
		if (empty($qry->rows)) {
			return false;
		}
		
		return true;
	}

	public function saveImportSkipRule($data) {

		if (empty($data['import_skip_rule_id'])) {
			$rec = array();
			$rec['column_name'] = $data['column_name'];
			$rec['pattern']     = $data['pattern'];
			$rec['rule_action'] = $data['rule_action'];
			$rec['sort_order']  = $data['sort_order'];
			$rec['import_group_id'] = $data['import_group_id'];
			$import_skip_rule_id = $this->kadb->queryInsert('ka_import_skip_rules', $rec);
		} else {
			$rec['column_name'] = $data['column_name'];
			$rec['pattern']     = $data['pattern'];
			$rec['rule_action'] = $data['rule_action'];
			$rec['sort_order']  = $data['sort_order'];
			$this->kadb->queryUpdate('ka_import_skip_rules', $rec, "import_skip_rule_id = '" . (int)$data['import_skip_rule_id'] . "'");
			$import_skip_rule_id = (int) $data['import_skip_rule_id'];
		}
		
		return $import_skip_rule_id;
	}
	
	
	public function deleteImportSkipRule($import_skip_rule_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "ka_import_skip_rules WHERE import_skip_rule_id = '" . (int)$import_skip_rule_id . "'");
	}

		
	public function getImportSkipRule($import_skip_rule_id) {
	
		$import_skip_rule = $this->db->query("SELECT * FROM " . DB_PREFIX . "ka_import_skip_rules 
			WHERE import_skip_rule_id = '" . (int)$import_skip_rule_id . "'"
		)->row;
		
		if (empty($import_skip_rule)) {
			return false;
		}
		
		return $import_skip_rule;
	}

	
	protected function getRecords($data = array()) {

		$language_id = (int)$this->config->get('config_language_id');
	
		$where = array();
		if (!empty($data['where'])) {
			$where[] = $data['where'];
		}

		if (isset($data['filter_import_group_id'])) {
			$where[] = "ka_isr.import_group_id = '" . (int)$data['filter_import_group_id'] . "'";
		} else {
			$where[] = "ka_isr.import_group_id = '0'";
		}
		
		if (empty($data['fields'])) {
			$fields = 'ka_isr.*';
		} else {
			$fields = $data['fields'];
		}
		
		$sql = "SELECT $fields FROM " . DB_PREFIX . "ka_import_skip_rules ka_isr";
		
		if (!empty($where)) {
			$sql .= " WHERE " . implode(" AND ", $where);
		}
		
		$sort_data = array(
			'column_name',
		);
		if (!empty($data['sort']) && in_array($data['sort'], $sort_data)) {
		
			$sql .= " ORDER BY " . $data['sort'];
			
			if (!empty($data['order'])) {
				$sql .= ' ' . $data['order'];
			}
		} else {
			$sql .= " ORDER BY sort_order, column_name";
		}
		
		
		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}				

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}
		
			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];

		}

		$query = $this->db->query($sql);
		
		return $query;
	}
	
	
	public function getImportSkipRules($data = array()) {
	
		$import_skip_rules = array();
	
		$qry = $this->getRecords($data);

		if (!empty($qry->rows)) {
			$import_skip_rules = $qry->rows;
		}
		
		return $import_skip_rules;
	}
	
	
	public function getImportSkipRulesTotal($data = array()) {
	
      	$data['fields'] = 'COUNT(*) AS total';

		if (isset($data['limit'])) {
			unset($data['limit']);
		}
		if (isset($data['start'])) {
			unset($data['start']);
		}

		$qry = $this->getRecords($data);
		
		if (empty($qry->row)) {
			return 0;
		}

		return $qry->row['total'];
	}
	
	
	public function getImportSkipRulesByImportGroupId($import_group_id) {
		$skip_rules = $this->db->query("SELECT * FROM " . DB_PREFIX . "ka_import_skip_rules 
			WHERE import_group_id = '" . (int)$import_group_id . "'
		")->rows;
		
		return $skip_rules;
	}
	
	/*
		Returns
			true - when the line matches
			false - when the line does not match
	*/
	public function isValueMatched($pattern, $value) {
	
		if (fnmatch($pattern, $value, FNM_CASEFOLD)) {
			return true;
		}
		
		return false;
	}
	

	/*
		Returns
			array - list of columns from the columns_in_use and available for the current import group
	*/
	public function getImportSkipRuleColumns($import_group_id, $all_columns) {

		$skip_rule_columns = array();
	
		$columns = $this->db->query("SELECT DISTINCT column_name FROM " . DB_PREFIX . "ka_import_skip_rules
			WHERE import_group_id = '" . (int)$import_group_id . "'
			ORDER BY sort_order
		")->rows;
		
		if (empty($columns)) {
			return $skip_rule_columns;
		}

		foreach ($columns as $ck => $cv) {
			$column_name = mb_strtolower(html_entity_decode($cv['column_name'], ENT_QUOTES, 'UTF-8'));

			foreach ($all_columns as $ack => $acv) {
				if (mb_strtolower($acv) == $column_name) {
					$skip_rule_columns[$acv] = $ack;
				}
			}
		}

		return $skip_rule_columns;
	}
	
	
	/*
		
	*/
	public function initSkipRulesCache($import_group_id, $columns) {
			
		$skip_rules = array();
	
		if (empty($columns)) {
			return $skip_rules;
		}
		
		// $ck - column name
		// $cv - column numeric identifier
		//
		foreach ($columns as $ck => $cv) {

			$qry = $this->db->query("SELECT * FROM " . DB_PREFIX . "ka_import_skip_rules WHERE
				import_group_id = '" . (int)$import_group_id . "'
				AND column_name = '" . $this->db->escape($this->request->clean($ck)) . "' 				
				ORDER BY sort_order
			");
			
			if (empty($qry->rows)) {
				continue;
			}

			$skip_rules[$ck] = array(
				'column'           => $cv,     // column numeric identifier
				'rules'            => array(), // cache for rules
				'values_cache'     => array(), // cache for pattern-value pairs
			);
			foreach ($qry->rows as $isk => $isv) {
				$skip_rules[$ck]['rules'][] = array(
					'pattern'     => $isv['pattern'],
					'rule_action' => $isv['rule_action'],
				);
			}
		}
		
		return $skip_rules;
	}
}