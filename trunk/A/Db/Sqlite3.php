<?php
/**
 * Sqlite3.php
 *
 * @package  A_Db
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD
 * @link	 http://skeletonframework.com/
 */

/**
 * A_Db_Sqlite3
 * 
 * Database connection class using SQLite.  Configuration array can contain the following indices: filename, mode.
 */
class A_Db_Sqlite3 extends A_Db_Adapter
{	protected $mode;
	protected $_sequence_ext = '_seq';
	protected $_sequence_start = 1;
	protected $_recordset_class = 'A_Db_Recordset_Sqlite3';
	protected $_result_class = 'A_Db_Result';
	
	public function __construct($config=null)
	{
		if (is_string($config)) {
			$config['filename'] = $config;
		}
		$this->mode = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;
		$this->config($config);
		if ($config && isset($config['autoconnect'])) {
			$this->connect();
		}
	}
	
	public function _connect ($config=null)
	{
		if (isset($config['filename'])) {
			if (! isset($config['mode'])) {
				$config['mode'] = $this->mode;
			}
			if (! isset($config['encryption_key'])) {
				$config['encryption_key'] = null;
			}
#echo "filename={$config['filename']}, mode={$config['mode']}, encryption_key={$config['encryption_key']}<br/>";
			$sqlite = new SQLite3($config['filename'], $config['mode'], $config['encryption_key']);
		} else {
			$this->_errorHandler(2, 'No filename. ');
		}
		return $sqlite; 
	}
	
	public function _close($sqlite)
	{
		if (isset($sqlite)) {
			$sqlite->close();
		} 
	}
	
	public function query($sql, $bind=array())
	{
		if (is_object($sql)) {
			// convert object to string by executing SQL builder object
			$sql = $sql->render($this);   // pass $this to provide db specific escape() method
		}
		if ($bind) {
			$prepare = new A_Sql_Prepare($sql, $bind);
			$prepare->setDb($this);
			$sql = $prepare->render();
		}
		$sqlite = $this->connectBySql($sql);
		if ($sqlite) {
			$result = $sqlite->query($sql);
			$this->_sql[] = $sql;			// save history
			$obj->error = $sqlite->lastErrorCode();
			$obj->errorMsg = $sqlite->lastErrorMsg();
			if (in_array(strtoupper(substr($sql, 0, 5)), array('SELEC','SHOW ','DESCR'))) {
				$this->_numRows = -1;	// $result->num_rows($result);
				$obj = new $this->_recordset_class($this->_numRows, $this->_error, $this->_errorMsg);
				// call RecordSet specific setters
				$obj->setResult($result);
			} else {
				$this->_numRows = $sqlite->changes();
				$obj = new $this->_result_class($this->_numRows, $this->_error, $this->_errorMsg);
			}
			return $obj;
		} else {
			$this->_errorHandler(3, 'No connection. ');
		}
		return $obj;
	}
	
	public function limit($sql, $count, $offset = null)
	{
		$limit = (is_int($offset) && $offset > 0) ? ($offset . ', ' . $count) : $count; 
		return $sql . ' LIMIT ' . $limit;
	}
	
	public function lastId()
	{
		$sqlite = $this->connectBySql('INSERT');
		if ($sqlite) {
			return($sqlite->lastInsertRowID());
		} else {
			return 0;
		}
	}
	
	public function nextId($sequence)
	{
	    return 0;
	}
	
	public function createSequence($sequence)
	{
	    return 0;
	}
		
	public function escape($value)
	{
		$sqlite = $this->connectBySql('SELECT');
		return $sqlite->escapeString($value);
	}
	
}