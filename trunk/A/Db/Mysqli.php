<?php
/**
 * Basic database connection functionality using the mysqli library
 * 
 * @package A_Db 
 */

class A_Db_Mysqli extends A_Db_Abstract {

	protected $dsn = null;
	protected $connected = false;
	protected $sequenceext = '_seq';
	protected $sequencestart = 1;
	protected $mysqli = null;
	
	public function __construct($dsn=null) {
		$this->dsn = $dsn;
	}
		
	public function _connect($dsn=null) {
		$result = false;
		if ($dsn) {
			$this->dsn = $dsn;
		}
		if (! $this->connected) {
			$this->mysqli = new MySQLi($this->dsn['hostspec'], $this->dsn['username'], $this->dsn['password']);
			if (isset($this->dsn['database'])) {
				$result = $this->mysqli->select_db($this->dsn['database'], $this->link);
			} else {
				$result = true;
			}
		}
		return $result;
	}
		
	public function _close($s='') {
		if ($db->connected) {
			$this->mysqli->close();
		} 
	}
		
	public function selectDb($database) {
		$this->dsn['database'] = $database;
		return $this->mysqli->select_db($this->dsn['database']);
	}
		
	public function query($sql, $bind=array()) {
		if (is_object($sql)) {
			// convert object to string by executing SQL builder object
			$sql = $sql->render($this);   // pass $this to provide db specific escape() method
		}
		if ($bind) {
			#include_once 'A/Sql/Prepare.php';
			$prepare = new A_Sql_Prepare($sql, $bind);
			$prepare->setDb($this->db);
			$sql = $prepare->render();
		}
		if (stripos($sql, 'select') === 0) {
			$obj = new A_Db_Mysqli_Recordset($this->mysqli->query($sql));
		} else {
			$obj = new A_Db_Mysqli_Result($this->mysqli->query($sql));
			$obj->affected_rows = $this->mysqli->affected_rows;
		}
		$obj->error = $this->mysqli->error;
		$obj->errorMsg = $this->mysqli->error;
		return $obj;
	}
		
	public function limit($sql, $count, $offset='') {
		if ($offset) {
			$count = "$count OFFSET $offset";
		} 
		return "$sql LIMIT $count";
	}
		
	public function lastId() {
		return $this->mysqli->insert_id();
	}
		
	public function nextId ($sequence) {
		if ($sequence) {
			$result = $this->mysqli->query("UPDATE $sequence{$this->sequenceext} SET id=LAST_INSERT_ID(id+1)");
			if ($result) {
				$id = $this->mysqli->insert_id();
				if ($id > 0) {
					return $id;
				} else {
					$result = $this->mysqli->query("INSERT $sequence{$this->sequenceext} SET id=1");
					$id = $this->mysqli->insert_id();
					if ($id > 0) {
						return $id;
					}
				}
			} elseif ($this->error() == 1146) {		// table does not exist
				if ($this->createSequence($sequence)) {
					return $this->sequencestart;
				}
			}
		}
		return 0;
	}
		
	public function createSequence ($sequence) {
		$result = 0;
		if ($sequence) {
			$result = $this->mysqli->query($this->link, "CREATE TABLE $sequence{$this->sequenceext} (id int(10) unsigned NOT NULL auto_increment, PRIMARY KEY(id)) TYPE=MyISAM AUTO_INCREMENT={$this->sequencestart}");
		}
		return($result);
	}
		
	public function escape($value) {
		return $this->mysqli->escape_string($this->link, $value);
	}
	
	public function isError() {
		return $this->mysqli->error;
	}
		
	public function getErrorMsg() {
		return $this->mysqli->error;
	}
	
	/**
	 * depricated name for getErrorMsg()
	 */
	public function getMessage() {
		return $this->getErrorMsg();
	}
	
	/**
	 * __call
	 * 
	 * Magic function __call, redirects to instance of MySQLi
	 * 
	 * @param string $function Function to call
	 * @param array $args Arguments to pass to $function
	 */
	function __call($function, $args)
	{
		return call_user_func_array(array($this->mysqli, $function), $args);
	}
}


class A_Db_Mysqli_Result {
	protected $result;
	protected $affected_rows;
	public $error;
	public $errorMsg;
	
	public function __construct($result=null) {
		$this->result = $result;
	}
		
	public function numRows() {
		if ($this->result) {
			return $this->affected_rows;
		} else {
			return 0;
		}
	}
		
	public function isError() {
		return $this->error;
	}
		
	public function getErrorMsg() {
		return $this->errorMsg;
	}

	/**
	 * depricated name for getErrorMsg()
	 */
	public function getMessage() {
		return $this->getErrorMsg();
	}
	
}




class A_Db_Mysqli_Recordset extends A_Db_Mysqli_Result {

public function __construct($result=null) {
	$this->result = $result;
}
	
public function fetchRow ($mode=null) {
	if ($this->result) {
		return $this->result->fetch_assoc($this->result);
	}
}
	
public function fetchObject ($class=null) {
	if ($this->result) {
		return $this->result->fetch_object($this->result, $class);
	}
}
	
public function fetchAll() {
	if ($this->result) {
		return $this->result->fetch_all($this->result);
	}
}
	
public function numRows() {
	if ($this->result) {
		return $this->result->num_rows;
	} else {
		return 0;
	}
}
	
public function numCols() {
	if ($this->result) {
		return $this->result->field_count;
	} else {
		return 0;
	}
}
	
public function __call($name, $args) {
	return call_user_func(array($this->result, $name), $args);
}
	
}
