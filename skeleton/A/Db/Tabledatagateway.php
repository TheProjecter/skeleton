<?php
include_once 'A/Sql/Select.php';

class A_Db_Tabledatagateway {
	protected $db;
	protected $table = '';
	protected $key = 'id';
	protected $columns = '*';
	protected $errmsg = '';
	public $sql = '';
	protected $num_rows = 0;
	
	public function __construct($db, $table='', $key='id') {
		$this->db = $db;
		$this->setTable($table);
		$this->key = $key;
		$this->select = new A_Sql_Select();
		$this->select->from($this->getTable());
	}

	public function setTable($table=null) {
		if ($table) {
			$this->table = $table;
		} else {
			$this->table = strtolower(get_class($this));
		}
		return $this;
	}
	
	public function getTable() {
		return $this->table;
	}
	
	public function setColumns($columns) {
		$this->columns = $columns;
		return $this;
	}
	
	public function where() {
		$args = func_get_args();
		// allow one param that is array of args
		if (is_array($args[0])) {
			$args = $args[0];
		}
		$nargs = count($args);
#print_r($args);
#echo "nargs=$nargs</br>";
		if ($nargs == 1) {
			// find match for key
			$this->select->where($this->key . '=', $args[0]);
		} else {
			$this->select->where($args[0], $args[1], isset($args[2]) ? $args[2] : null);
		}
		return $this;
	}

	public function find() {
		$allrows = array();

		$args = func_get_args();
		// if params then where condition passed
		if (count($args)) {
			$this->where($args);
		}

		$this->sql = $this->select->render();
		$result = $this->db->query($this->sql);
		if ($result->isError()) {
			$this->errmsg = $result->getMessage();
		} else {
			while ($row = $result->fetchRow()) {
				$allrows[] = $row;
			}
			$this->num_rows = count($allrows);
		}
		return $allrows;
	}
	
	public function update($id, $data) {
		if ($id && $data) {
			if (isset($data[$this->key])) {
				unset($data[$this->key]);
			}
			foreach ($data as $field => $value) {
				$sets[] = $field . '=' . $this->quoteEscape($value);
			}
			$this->sql = "UPDATE {$this->table} SET " . implode(',', $sets) . " WHERE {$this->key}='$id'";
			$this->db->query($this->sql);
		}
	}
	
	public function insert($data) {
		if ($data) {
			if (empty($data[$this->key])) {			// remove array element for key unless it contains a value
				unset($data[$this->key]);
			}
			foreach ($data as $field => $value) {
				$cols[] = $field;
				$values[] = $this->quoteEscape($value);
			}
			$this->sql = "INSERT INTO {$this->table} (" . implode(',', $cols) . ') VALUES (' . implode(',', $values) . ')';
			$this->db->query($this->sql);
			return $this->db->lastId();
		}
	}
	
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
		return $this->db->isError();
	}
	
	public function getMessage() {
		return $this->db->getMessage();
	}
	
	
}

