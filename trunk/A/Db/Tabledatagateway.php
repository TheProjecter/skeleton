<?php
/**
 * Datasource access using the Table Data Gateway pattern
 *
 * @package A_Db
 */
class A_Db_Tabledatagateway {
	protected $db;
	protected $table = '';
	protected $key = 'id';
	protected $columns = '*';
	protected $errmsg = '';
	public $sql = '';
	protected $num_rows = 0;
	protected $insert = null;
	
	public function __construct($db, $table=null, $key=null) {
		$this->db = $db;
		$this->table($table);
		$this->key($key);
		$this->select = new A_Sql_Select();
		$this->select->from($this->getTable());
	}

	public function table($table=null) {
		if ($table) {
			$this->table = $table;
		} elseif ($this->table == '') {
			$this->table = strtolower(get_class($this));
		}
		return $this;
	}
	
	public function getTable() {
		return $this->table;
	}
	
	public function key($key='') {
		$this->key = $key ? $key : 'id';
		return $this;
	}
	
	public function getKey() {
		return $this->key;
	}
	
	public function columns($columns) {
		$this->columns = $columns;
		return $this;
	}
	
	public function where($arg1=null, $arg2=null, $arg3=null) {
		if (isset($arg1)) {
			$this->select->where($arg1, $arg2, $arg3);
		} else {
			$this->select->where();				// no args - clear where
		}
		return $this;
	}

	public function find() {
		$allrows = array();

		$this->select->where();			// clear where clause

		$args = func_get_args();
		// if params then where condition passed
		if (count($args)) {
			if (is_array($args[0])) {
				$args = $args[0];
			} elseif (! isset($args[1])) {	// single scalar param is key search
				$args = array($this->key => $args[0]);
			}
			$this->where($args);
		}

		$this->sql = $this->select
						->columns($this->columns)
						->from($this->getTable())
						->render();
		$result = $this->db->query($this->sql);
		if (! $result->isError()) {
			while ($row = $result->fetchRow()) {
				$allrows[] = $row;
			}
			$this->num_rows = count($allrows);
		} else {
			$this->errmsg = $result->getErrorMsg();
		}
		return $allrows;
	}
	
	public function update($data, $where='') {
		if ($data) {
			if (! $this->update) {
				#include_once 'A/Sql/Update.php';
				$this->update = new A_Sql_Update($this->getTable());
			}
			$this->update->setDb($this->db)->set($data);
			if ($where) {
				if (is_array($where)) {
					$this->update->where($where);
				} else {
					$this->update->where($this->key, $where);
				}
			}
			$this->sql = $this->update->render();
			return $this->db->query($this->sql);
		}
	}
	
	public function insert($data) {
		if ($data) {
			if (! $this->insert) {
				#include_once 'A/Sql/Insert.php';
				$this->insert = new A_Sql_Insert($this->getTable());
			}
			$this->sql = $this->insert->setDb($this->db)->values($data)->render();
			return $this->db->query($this->sql);
		}
	}
	
	public function save($data) {
		if ($data) {
			if (isset($data[$this->key]) && $data[$this->key]) {
				#include_once 'A/Sql/Insert.php';
				$this->update($data, $data[$this->key]);
			} else {
				$this->insert($data);
			}
		}
	}
	
/*
	public function update($data, $where='') {
		if ($data && $where) {
			if (isset($data[$this->key])) {
				unset($data[$this->key]);
			}
			foreach ($data as $field => $value) {
				$sets[] = $field . "='" . $this->db->escape($value) . "'";
			}
			$this->sql = "UPDATE {$this->table} SET " . implode(',', $sets) . " WHERE {$this->key}='$id'";
			$this->db->query($this->sql);
		}
	}
	
	public function insert($data) {
		if ($data) {
			// if one row then 1st element is scalar
			if(! is_array(current($data))) {
				$cols = array_keys($data);
				$data = array($data);
			} else {
				$cols = array_keys(current($data));
			}
			$values = array();
			foreach ($data as $row) {
				if (empty($row[$this->key])) {			// remove array element for key unless it contains a value
					unset($row[$this->key]);
					unset($cols[$this->key]);
				}
				foreach ($row as $key => $value) {
					$row[$key] = $this->db->escape($value);
				}
				$values[] = "('" . implode("','", $row) . "')";
			}
			$this->sql = "INSERT INTO {$this->table} (" . implode(',', $cols) . ') VALUES ' . implode(',', $values);
			$this->db->query($this->sql);
			return $this->db->lastId();
		}
	}
*/
	
	public function delete($id) {
		if ($id) {
			$this->sql = "DELETE FROM {$this->table} WHERE {$this->key}='$id'";
			$this->db->query($this->sql);
		}
	}
	
	public function numRows() {
		return $this->num_rows;
	}
	
	public function isError() {
		return $this->db->isError() || ($this->errmsg != '');
	}
	
	public function getErrorMsg() {
		return $this->db->getErrorMsg() . $this->errmsg;
	}
	
	
}
