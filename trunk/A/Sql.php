<?php

class A_Sql {
	protected $connection;
	protected $select = null;
	protected $insert = null;
	protected $update = null;
	protected $delete = null;
		
	public function __construct($connection=null) {
		$this->connection = $connection;
	}
		
	public function __call($name, $param) {
		switch ($name) {
			case 'select':
				if (!$this->select) {
					include_once 'A/Sql/Select.php';
					$this->select = new A_Sql_Select($this->connection);
				}
				return $this->select->columns($param);
			break;
			case 'insert':
				if (!$this->insert) {
					include_once 'A/Sql/Insert.php';
					$this->insert = new A_Sql_Insert($this->connection);
				}
				return $this->insert->table($param);
			break;
			case 'update':
				if (!$this->update) {
					include_once 'A/Sql/Update.php';
					$this->update = new A_Sql_Update($this->connection);
				}
				return $this->update->table($param);
			break;
			case 'delete':
				if (!$this->delete) {
					include_once 'A/Sql/Delete.php';
					$this->delete = new A_Sql_Delete($this->connection);
				}
				return $this->delete->table($param);
			break;
		}
	}
}