<?php
/*
	$Project: CSV Product Import $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 6.0.0.2 $ ($Revision: 572 $)
*/

namespace extension\ka_product_import;

class ModelImportProfiles extends \extension\ka_extensions\Model {

	public function getProfiles() {
		$qry = $this->db->query("SELECT import_profile_id, name FROM " . DB_PREFIX . "ka_import_profiles");

		$profiles = array();				
		if (!empty($qry->rows)) {
			foreach ($qry->rows as $row) {
				$profiles[$row['import_profile_id']] = $row['name'];
			}
		}
				
		return $profiles;
	}


	public function deleteProfile($profile_id) {

		$this->db->query("DELETE FROM " . DB_PREFIX. "ka_import_profiles WHERE import_profile_id = '" . $this->db->escape($profile_id) . "'");
			
		return true;
	}
	
	
	public function getProfileIdByName($name) {
	
		$qry = $this->db->query("SELECT import_profile_id, name FROM " . DB_PREFIX . "ka_import_profiles WHERE name = '" . $this->db->escape($name) . "'");
		if (empty($qry->rows)) {
			return false;
		}

		
		return $qry->row['import_profile_id'];
	}
	
	
	public function getProfile($profile_id) {
		$qry = $this->db->query("SELECT * FROM " . DB_PREFIX . "ka_import_profiles WHERE import_profile_id = '" . (int)$profile_id . "'");
		if (empty($qry->rows)) {
			return array();
		}
		
		$profile = $qry->row;
		
		if (!empty($qry->row['params'])) {
			$profile['params'] = unserialize($qry->row['params']);
		} else {
			$profile['params'] = array();
		}
		
		return $profile;
	}
	
		
	public function getProfileParams($profile_id) {
	
		$profile = $this->getProfile($profile_id);
		if (empty($profile)) {
			return $profile;
		}
		
		return $profile['params'];
	}
	
	
	/*
		returns:
			true  - on success
			false - on error
	*/
	public function setProfileParams($profile_id, $name, $params) {
	
		if (empty($profile_id)) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "ka_import_profiles
				SET 
					name = '" . $this->db->escape($name) . "'
			");
			$profile_id = $this->db->getLastId();
		}
		
		$this->db->query("UPDATE " . DB_PREFIX . "ka_import_profiles
			SET 
				name = '" . $this->db->escape($name) . "',
				params = '" . $this->db->escape(serialize($params)) . "'
			WHERE
				import_profile_id = '" . $this->db->escape($profile_id) . "'
		");

		return $profile_id;
	}
}