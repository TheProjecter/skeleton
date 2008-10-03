<?php
/**
 * Database connectin class using the mysql_ library
 *
 * @package A_Db
 * 
 * config array contain:
 * 'hostspec'
 * 'username'
 * 'password'
 * 'database'
 */
class A_Db_MySQL {
	protected $config = null;
	protected $link = null;
	protected $limit = '';
	protected $orderby = '';
	protected $sequenceext = '_seq';
	protected $sequencestart = 1;
	
	public function __construct($config=null) {
		$this->config = $config;
	}
		
	public function connect ($config=null) {
		$result = false;
		if ($config) {
			$this->config = $config;
		}
		if ($this->link == null) {
			$host = isset($this->config['host']) ? $this->config['host'] : $this->config['hostspec'];
			$this->link = mysql_connect($host, $this->config['username'], $this->config['password']);
			if ($this->link) {
				if (isset($this->config['database'])) {
					$result = mysql_select_db($this->config['database'], $this->link);
				} else {
					$result = true;
				}
			}
		}
		return $result;
	}
		
	public function close() {
		if ($this->link) {
			mysql_close($this->link);
		} 
	}
		
	public function query ($sql) {
		if (is_object($sql)) {
			// convert object to string by executing SQL builder object
			$sql = $sql->render($this);   // pass $this to provide db specific escape() method
		}
		mysql_select_db($this->config['database'], $this->link);
		if (strpos(strtolower($sql), 'select') === 0) {
			$obj = new A_Db_MySQL_Recordset(mysql_query($sql));
		} else {
			$obj = new A_Db_MySQL_Result(mysql_query($sql));
		}
		$obj->errno = mysql_errno($this->link);
		$obj->errmsg = mysql_error($this->link);
		return $obj;
	}
		
	public function limit($sql, $count, $offset = null) {
		$limit = (is_int($offset) && $offset > 0) ? ($offset . ', ' . $count) : $count; 
		return $sql . ' LIMIT ' . $limit;
	}
	
	public function lastId() {
		if ($this->link) {
			return(mysql_insert_id($this->link));
		} else {
			return 0;
		}
	}
		
	public function nextId ($sequence) {
	    if ($sequence) {
		    $result = mysql_query("UPDATE $sequence{$this->sequenceext} SET id=LAST_INSERT_ID(id+1)", $this->link);
	    	if ($result) {
		        $id = mysql_insert_id($this->link);
		        if ($id > 0) {
		            return $id;
		        } else {
				    $result = mysql_query("INSERT $sequence{$this->sequenceext} SET id=1", $this->link);
			        $id = mysql_insert_id($this->link);
			        if ($id > 0) {
			            return $id;
			        }
		        }
			} elseif (mysql_errno() == 1146) {		// table does not exist
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
		    $result = mysql_query("CREATE TABLE $sequence{$this->sequenceext} (id int(10) unsigned NOT NULL auto_increment, PRIMARY KEY(id)) TYPE=MyISAM AUTO_INCREMENT={$this->sequencestart}", $this->link);
	    }
	    return($result);
	}
		
	public function start() {
		return mysql_query('START');
	}

	public function savepoint($savepoint='') {
		if ($savepoint) {
			return mysql_query('SAVEPOINT ' . $savepoint);
		}
	}

	public function commit() {
		return mysql_query('COMMIT');
	}

	public function rollback($savepoint='') {
		return mysql_query('ROLLBACK' . ($savepoint ? ' TO SAVEPOINT ' . $savepoint : ''));
	}

	public function escape($value) {
		return mysql_real_escape_string($value, $this->link);
	}

	public function isError() {
		return mysql_errno($this->link);
	}
		
	public function getMessage() {
		return mysql_error($this->link);
	}
		
}
	
	
class A_Db_MySQL_Result {
	protected $result;
	public $errno;
	public $errmsg;
	
	public function __construct($result=null) {
		$this->result = $result;
	}
		
	public function numRows() {
		if ($this->result) {
			return(mysql_affected_rows($this->result));
		} else {
			return 0;
		}
	}
		
	public function isError() {
		return $this->errno;
	}
		
	public function getMessage() {
		return $this->errmsg;
	}
	
}
	
	
class A_Db_MySQL_Recordset extends A_Db_MySQL_Result {
	
	public function __construct($result=null) {
		$this->result = $result;
	}
		
	public function fetchRow ($mode=null) {
		if ($this->result) {
			return mysql_fetch_assoc($this->result);
		}
	}
		
	public function fetchObject ($mode=null) {
		if ($this->result) {
			return mysql_fetch_object($this->result);
		}
	}
		
	public function numRows() {
		if ($this->result) {
			return mysql_num_rows($this->result);
		} else {
			return 0;
		}
	}
		
	public function numCols() {
		if ($this->result) {
			return mysql_num_cols($this->result);
		} else {
			return 0;
		}
	}
	
}