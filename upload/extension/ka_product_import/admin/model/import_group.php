<?php 
/*
	$Project: CSV Product Import $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 6.0.0.2 $ ($Revision: 572 $)
*/

namespace extension\ka_product_import;

use \extension\ka_extensions\QB;

class ModelImportGroup extends \extension\ka_extensions\Model {

	public function saveImportGroup($data) {

		if (empty($data['import_group_id'])) {
			$rec = array();
			$rec['name'] = $data['name'];
			$import_group_id = $this->kadb->queryInsert('ka_import_groups', $rec);
		} else {
			$rec['name'] = $data['name'];
			$this->kadb->queryUpdate('ka_import_groups', $rec, "import_group_id = '" . (int)$data['import_group_id'] . "'");
			$import_group_id = (int) $data['import_group_id'];
		}
		return $import_group_id;
	}
	
	
	public function deleteImportGroup($import_group_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "ka_import_groups WHERE import_group_id = '" . (int)$import_group_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "ka_import_skip_rules WHERE import_group_id = '" . (int)$import_group_id . "'");
	}

		
	public function getImportGroup($import_group_id) {
	
		$import_group = $this->db->query("SELECT * FROM " . DB_PREFIX . "ka_import_groups 
			WHERE import_group_id = '" . (int)$import_group_id . "'"
		)->row;
		
		if (empty($import_group)) {
			return false;
		}
		
		return $import_group;
	}

	
	protected function getRecordsQB($data = array()) {
	
		$qb = new QB();
		
		if (empty($data['fields'])) {
			$qb->select("*", "ka_import_groups", "ka_ig");
		} else {
			$qb->select($data['fields'], "ka_import_groups", "ka_ig");
		}
		
		if (!empty($data['where'])) {
			$qb->where(implode(" AND ", $where));
		}

		return $qb;
	}
	
	
	public function getImportGroups($data = array()) {

		$qb = $this->getRecordsQB($data);
	
		if (!empty($data['sort'])) {
			$sql = $data['sort'];
			if (!empty($data['order'])) {
				$sql .= " " . $data['order'];
			}
			$qb->orderBy($sql);
		} else {
			$qb->orderBy("name ASC");
		}
		
		if (isset($data['start']) && isset($data['limit'])) {
			$qb->limit(max(0, $data['start']), max(1, $data['limit']));
		}
	
		$import_groups = $qb->query()->rows;

		return $import_groups;
	}
	
	
	public function getImportGroupsTotal($data = array()) {
	
      	$data['fields'] = 'COUNT(*) AS total';

		$result = $this->getRecordsQB($data)->query()->row;
      	
		if (empty($result)) {
			return 0;
		}

		return $result['total'];
	}	
}