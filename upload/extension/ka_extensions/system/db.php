<?php
/*
	$Project: Ka Extensions $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 4.1.1.0 $ ($Revision: 269 $)
*/
	
namespace extension\ka_extensions;

class Db {

	protected $db = null;
	
	public function __construct($db) {
		$this->db = $db;
	}
		
 	public function query($sql) {
 		$res = $this->db->query($sql);

		if (empty($res->rows)) {
			return $res;
		}
 		
		return $res->rows;
 	}

 	
 	/*
 	*/
 	public function insertOrUpdate($table, $data) {
 	
 		return $this->insert($table, $data, false, true);
 	}

 	
 	/*
 		Deprecated function, use insert instead.
 	*/
	public function queryInsert($tbl, $arr, $is_replace = false) { 	
		return $this->insert($tbl, $arr, $is_replace);
	}
	
 	
	public function insert($tbl, $arr, $is_replace = false, $update_on_duplicate = false) {

	    if (empty($tbl) || empty($arr) || !is_array($arr)) {
	    	throw new \Exception(__METHOD__ . ": Wrong parameters");
	    }

	    $query = $is_replace ? 'REPLACE' : 'INSERT';

    	$r = array();
    	
	    foreach ($arr as $k => $v) {
	    	if (is_numeric($k)) {
	    		$r[] = $v;
	    		continue;
	    	}
	    
	    	if (!(($k[0] == '`') && ($k[strlen($k) - 1] == '`'))) {
    	        $k = "`$k`";
        	}

        	if (!is_null($v)) {
		        $v = "'" . $this->db->escape($v) . "'";
			} else {
				$v = 'NULL';
			}
    		$r[] = $k . "=" . $v;
    	}

    	$tbl = DB_PREFIX . $tbl;
    	$query .= ' INTO `' . $tbl . '` SET ' . implode(', ', $r);

    	if ($update_on_duplicate) {
    		$query .= " ON DUPLICATE KEY UPDATE " . implode(', ', $r);
    	}
    	
    	if (!$this->db->query($query)) {
	    	return false;
		}

		return $this->db->getLastId();
	}

	/*
		Deprecated. use update() instead.
	*/
	public function queryUpdate($tbl, $arr, $where = '') {
		$this->update($tbl, $arr, $where);
	}
	
	public function update($tbl, $arr, $where = '') {
	    if (empty($tbl) || empty($arr) || !is_array($arr)) {
    	    throw new \Exception(__METHOD__ . ": wrong parameters");
	    }

		$tbl = DB_PREFIX . $tbl;

    	$r = array();

	    foreach ($arr as $k => $v) {
	    	if (is_numeric($k)) {
	    		$r[] = $v;
	    		continue;
	    	}
	    
	    	if (!(($k[0] == '`') && ($k[strlen($k) - 1] == '`'))) {
    	        $k = "`$k`";
        	}

        	if (!is_null($v)) {
		        $v = "'" . $this->db->escape($v) . "'";
			} else {
				$v = 'NULL';
			}
    		$r[] = $k . "=" . $v;
    	}

	    $query = 'UPDATE `' . $tbl . '` SET ' . implode(', ', $r) . ($where ? ' WHERE ' . $where : '');

    	return $this->db->query($query);
	}

	/*
		Deprecated function
	*/
	public function queryFirst($qry) {
		$res = $this->db->query($qry);
		return $res->row;
	}

	public function safeQuery($query) {
	
		if (in_array('MijoShop', get_declared_classes())) {
			$prefix = DB_PREFIX;
			$prefix = MijoShop::get('db')->getDbo()->replacePrefix($prefix);
			$query = str_replace(DB_PREFIX, $prefix, $query);
		}
		
		$result = $this->db->query($query);
		
		return $result;
	}
	
	public function queryHash($qry_string, $key) {
	
		$qry = $this->db->query($qry_string);
		if (empty($qry->rows)) {
			return false;
		}
		
		$res = array();
		if (!isset($qry->row[$key])) {
			throw new \Exception(__METHOD__ . ": key not found ($key)");
		}
		
		foreach ($qry->rows as $row) {
			if (isset($row[$key])) {
				$res[$row[$key]] = $row;
			}
		}
		
		return $res;
	}
	
	
	/*
		it works with varchar(...) type only right now
		
		RETURNS:
			array where keys are fields, values are field lengths
	*/
	public function getFieldLengths($table, $fields) {
	
		$qry = $this->db->query("DESCRIBE `$table`");
		if (empty($qry->rows) || empty($fields)) {
			return false;
		}
		
		$ret = array_combine($fields, array_fill(0, count($fields), 0));
		foreach ($qry->rows as $f) {

			if (!in_array($f['Field'], $fields)) {
				continue;
			}
		
			if (!preg_match("/varchar\((\d*)\)/", $f['Type'], $matches)) {
				continue;
			}
			
			$ret[$f['Field']] = intval($matches[1]);
		}
		
		return $ret;	
	}
	
}