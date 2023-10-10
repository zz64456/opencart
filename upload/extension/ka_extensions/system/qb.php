<?php
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
*/
	
namespace extension\ka_extensions;

// Query Builder
class QB {

	var $select    = array();
	var $from      = array();
	var $innerJoin = array();
	var $leftJoin  = array();
	var $where     = array();
	var $limit     = array();
	var $orderBy   = array();
	var $groupBy   = array();
	
	protected $db = null;

	public function __construct() {
		$this->db = KaGlobal::getRegistry()->get('db');
	}
	
	public function from($from, $from_key = '') {
		if (!empty($from_key)) {
			$this->from[$from_key] = $from;
		} else {
			$this->from[$from] = $from;
		}
		
		return $this;
	}

	
	public function select($what, $from = '', $from_key = '') {
		
		$this->select[] = $what;
		
		if (!empty($from)) {
			$this->from($from, $from_key);
		}
		
		return $this;
	}
	
	
	public function innerJoin($from, $from_key = '', $condition = '') {
		
		$arr = array(
			'table' => $from,
			'on' => $condition
		);
	
		if (!empty($from_key)) {
			$this->innerJoin[$from_key] = $arr;
		} else {
			$this->innerJoin[$from] = $arr;
		}
		
		return $this;
	}

	public function leftJoin($from, $from_key = '', $condition = '') {
		
		$arr = array(
			'table' => $from,
			'on' => $condition
		);
	
		if (!empty($from_key)) {
			$this->leftJoin[$from_key] = $arr;
		} else {
			$this->leftJoin[$from] = $arr;
		}
		
		return $this;
	}
	
	/*
		$where - string or array. The array means all condtions inside the array have OR.		
	*/
	public function where($where, $value = null) {
		if (!is_null($value)) {
			$where = "$where = '" . $this->db->escape($value) . "'";
		}
		$this->where[] = $where;
		
		return $this;
	}	
	
	public function limit($start, $limit) {
		$this->limit = array(
			'start' => $start,
			'limit' => $limit
		);
		
		return $this;
	}
	
	public function orderBy($order, $after = '') {
	
		if (empty($after)) {
			$this->orderBy[$order] = $order;
		} else {
			$this->orderBy = Arrays::insertAfterKey($this->orderBy, $order, $after);
		}
		
		return $this;
	}

	public function groupBy($groupBy, $after = '') {
	
		if (empty($after)) {
			$this->groupBy[$groupBy] = $groupBy;
		} else {
			$this->groupBy = Arrays::insertAfterKey($this->groupBy, $groupBy, $after);
		}
		
		return $this;
	}
	
	public function getSql() {

		$sql = '';
		
		// select parameters
		//
		if (!empty($this->select)) {
			$sql .= " SELECT " . implode(",", $this->select) . " ";
		}
		
		// from parameters
		//
		if (!empty($this->from)) {
			$sql .= " FROM ";
			foreach ($this->from as $k => $v) {			
				$sql .= DB_PREFIX . $v;
				if ($v != $k) {
					$sql .= " " . $k;
				}
			}
		}
		
		// inner join parameters
		//
		if (!empty($this->innerJoin)) {
			foreach ($this->innerJoin as $k => $v) {
				$sql .= " INNER JOIN " . DB_PREFIX . $v['table'] . ' ' . $k;
				
				if (!empty($v['on'])) {
					$sql .= " ON " . $v['on'] . " ";
				}
			}
		}

		// left join parameters
		//
		if (!empty($this->leftJoin)) {
			foreach ($this->leftJoin as $k => $v) {
				$sql .= " INNER JOIN " . DB_PREFIX . $v['table'] . ' ' . $k;
				
				if (!empty($v['on'])) {
					$sql .= " ON " . $v['on'] . " ";
				}
			}
		}
		
		// where parameters
		//
		if (!empty($this->where)) {
			$where = "";
			foreach ($this->where as $k => $v) {
				if (!empty($where)) {
					$where .= " AND ";
				}
				if (is_array($v)) {
					$where .= ' (' . implode(" OR ", $v) . ') ';
				} else {
					$where .= ' (' . $v . ') ';
				}
			}
			$sql .= " WHERE $where";
		}

		// group by
		//
		if (!empty($this->groupBy)) {
			$sql .= " GROUP BY " . implode($this->groupBy);
		}
		
		// order by
		//
		if (!empty($this->orderBy)) {
			$sql .= " ORDER BY " . implode($this->orderBy);
		}
		
		// limit
		//
		if (!empty($this->limit)) {
			if (isset($this->limit['start'])) {
				$sql .= " LIMIT " . $this->limit['start'];
				
				if (isset($this->limit['limit'])) {
					$sql .= ", " . $this->limit['limit'];
				}
			}
		}
		
		return $sql;
	}
	
	
	/*
		Builds and runs the query from the QB data
	*/
	public function query() {
		return $this->db->query($this->getSql());
	}
	
}