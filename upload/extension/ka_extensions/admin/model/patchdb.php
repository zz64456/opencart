<?php
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
*/

namespace extension\ka_extensions;

class ModelPatchDb extends Model {

	/*
		Compatible db may be fully patched or not patched at all. Partial changes are
		treated as a corrupted db.

		The method extends tables information with an 'exists' flag for existing elements.
		
		Returns
			true  - db is compatible
			false - db is not compatible

	*/
	public function checkDBCompatibility(&$tables, &$messages) {
	
		$messages = array();

		if (empty($tables)) {
			return true;
		}

		foreach ($tables as $tk => $tv) {

			$tbl = DB_PREFIX . $tk;
			$res = $this->kadb->safeQuery("SHOW TABLES LIKE '$tbl'");

			if (!empty($res->rows)) {
				$tables[$tk]['exists'] = true;
			} else {
				continue;
			}

			$fields = $this->kadb->safeQuery("DESCRIBE `$tbl`");
			if (empty($fields->rows)) {
				$messages[] = "Table '$tbl' exists in the database but it is empty.";
				return false;
			}

			// check fields 

			$db_fields = array();
			foreach ($fields->rows as $v) {
				$db_fields[$v['Field']] = array(
					'type'  => $v['Type']
				);
			}

			foreach ($tv['fields'] as $fk => $field) {
			
				if (empty($db_fields[$fk])) {
					continue;
				}

				// if the field is found we validate its type

				$db_field = $db_fields[$fk];
				
				$tables[$tk]['fields'][$fk]['exists'] = true;
			}

			// check indexes
			/*
				We do not compare index fields yet, just ensure that the index with the appropriate
				name exists for the table.
			*/
			if (!empty($tv['indexes'])) {

				$rec = $this->kadb->safeQuery("SHOW INDEXES FROM `$tbl`");
				$db_indexes = array();
				foreach ($rec->rows as $v) {
					$db_indexes[$v['Key_name']]['columns'][] = $v['Column_name'];
				}

				foreach ($tv['indexes'] as $ik => $index) {
					if (!empty($db_indexes[$ik])) {
						$tables[$tk]['indexes'][$ik]['exists'] = true;
					}
				}
			}
		}

		return true;
	}

			
	public function patchDB($tables, &$messages) {
		$messages = array();
		
		if (empty($tables)) {
			return true;
		}

		$this->db->query("SET sql_mode = ''");
		
		foreach ($tables as $tk => $tv) {
			if (empty($tv['exists'])) {
				$this->kadb->safeQuery($tv['query']);
				continue;
			}

			if (!empty($tv['fields'])) {
				foreach ($tv['fields'] as $fk => $fv) {
					if (empty($fv['exists'])) {
					
						if (!empty($fv['exists_different'])) {

							if (!empty($fv['query_change'])) {
								$this->kadb->safeQuery($fv['query_change']);
							} else {
								$messages[] = "Installation error. The field with a different type cannot be changed: " . $tk . "." . $fk;
								return false;
							}
							continue;
							
						} else if (!empty($fv['query'])) {
							$this->kadb->safeQuery($fv['query']);
							continue;
						}
						
						$messages[] = "Installation error. Cannot create '$tk.$fk' field.";
						return false;
					}
				}
			}

			if (!empty($tv['indexes'])) {
				foreach ($tv['indexes'] as $ik => $iv) {
					if (empty($iv['exists']) && !empty($iv['query'])) {
						$this->kadb->safeQuery($iv['query']);
					}
				}
			}
		}
	
		return true;
	}
}