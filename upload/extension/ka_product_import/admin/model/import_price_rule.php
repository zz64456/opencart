<?php 
/*
	$Project: CSV Product Import $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 6.0.0.2 $ ($Revision: 572 $)
*/

namespace extension\ka_product_import;

class ModelImportPriceRule extends \extension\ka_extensions\Model {

	public function isInstalled() {

		$qry = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "ka_import_price_rules'");
		
		if (empty($qry->rows)) {
			return false;
		}
		
		return true;
	}

	public function saveImportPriceRule($data) {

		if (empty($data['import_price_rule_id'])) {
			$rec = array();
			$rec['column_name'] = $data['column_name'];
			$rec['pattern']     = $data['pattern'];
			$rec['price_multiplier'] = $data['price_multiplier'];
			$rec['sort_order']  = $data['sort_order'];
			$rec['import_group_id'] = $data['import_group_id'];
			$import_price_rule_id = $this->kadb->queryInsert('ka_import_price_rules', $rec);
		} else {
			$rec['column_name'] = $data['column_name'];
			$rec['pattern']     = $data['pattern'];
			$rec['price_multiplier'] = $data['price_multiplier'];
			$rec['sort_order']  = $data['sort_order'];
			$this->kadb->queryUpdate('ka_import_price_rules', $rec, "import_price_rule_id = '" . (int)$data['import_price_rule_id'] . "'");
			$import_price_rule_id = (int) $data['import_price_rule_id'];
		}
		
		return $import_price_rule_id;
	}
	
	
	public function deleteImportPriceRule($import_price_rule_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "ka_import_price_rules WHERE import_price_rule_id = '" . (int)$import_price_rule_id . "'");
	}

		
	public function getImportPriceRule($import_price_rule_id) {
	
		$import_price_rule = $this->db->query("SELECT * FROM " . DB_PREFIX . "ka_import_price_rules 
			WHERE import_price_rule_id = '" . (int)$import_price_rule_id . "'"
		)->row;
		
		if (empty($import_price_rule)) {
			return false;
		}
		
		return $import_price_rule;
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
		
		$sql = "SELECT $fields FROM " . DB_PREFIX . "ka_import_price_rules ka_isr";
		
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
	
	
	public function getImportPriceRules($data = array()) {
	
		$import_price_rules = array();
	
		$qry = $this->getRecords($data);

		if (!empty($qry->rows)) {
			$import_price_rules = $qry->rows;
		}
		
		return $import_price_rules;
	}
	
	
	public function getImportPriceRulesTotal($data = array()) {
	
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
	
	
	public function getImportPriceRulesByImportGroupId($import_group_id) {
		$price_rules = $this->db->query("SELECT * FROM " . DB_PREFIX . "ka_import_price_rules 
			WHERE import_group_id = '" . (int)$import_group_id . "'
		")->rows;
		
		return $price_rules;
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
	public function getImportPriceRuleColumns($import_group_id, $all_columns) {

		$price_rule_columns = array();
	
		$columns = $this->db->query("SELECT DISTINCT column_name FROM " . DB_PREFIX . "ka_import_price_rules
			WHERE import_group_id = '" . (int)$import_group_id . "'
			ORDER BY sort_order
		")->rows;
		
		if (empty($columns)) {
			return $price_rule_columns;
		}
		
		foreach ($columns as $ck => $cv) {
			$column_name = mb_strtolower(html_entity_decode($cv['column_name'], ENT_QUOTES, 'UTF-8'));

			foreach ($all_columns as $ack => $acv) {
				if (mb_strtolower($acv) == $column_name) {
					$price_rule_columns[$acv] = $ack;
				}
			}
		}

		return $price_rule_columns;
	}
	
	
	/*

		Parameters:
			import_group_id - a group identifier
			columns - array of columns
				[<column name>] => [column id]
				...
	
		Returns an initial list of rules by columns.
		
		[<column name>]
			[column] => <column id>
			[values_cache]  => [ - always empty by default
				[cell value] => [price modifier]
				...
			]
			[rules] => [	=> 
				[pattern]
				[price modifier]
			]
		]
	
	*/
	public function initPriceRulesCache($import_group_id, $columns) {
			
		$price_rules = array();
	
		if (empty($columns)) {
			return $price_rules;
		}
		
		// $ck - column name
		// $cv - column numeric identifier
		//
		foreach ($columns as $ck => $cv) {

			$qry = $this->db->query("SELECT * FROM " . DB_PREFIX . "ka_import_price_rules WHERE
				import_group_id = '" . (int)$import_group_id . "'
				AND column_name = '" . $this->db->escape($this->request->clean($ck)) . "' 				
				ORDER BY sort_order
			");
			
			if (empty($qry->rows)) {
				continue;
			}

			$price_rules[$ck] = array(
				'column'           => $cv,     // column numeric identifier
				'rules'            => array(), // cache for rules
				'values_cache'     => array(), // cache for pattern-value pairs
			);
			foreach ($qry->rows as $isk => $isv) {
				$price_rules[$ck]['rules'][] = array(
					'pattern'          => $isv['pattern'],
					'price_multiplier' => $isv['price_multiplier'],
				);
			}
		}

		return $price_rules;
	}
}