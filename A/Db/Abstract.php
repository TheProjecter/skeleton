<?php
/**
 * Class for connecting to SQL databases and performing common database operations.
 *
 */
abstract class A_Db_Abstract
{
	const FETCH_ASSOC = 2;
    const FETCH_BOTH = 4;
    const FETCH_BOUND = 6;
    const FETCH_CLASS = 8;
    const FETCH_CLASSTYPE = 262144;
    const FETCH_COLUMN = 7;
    const FETCH_FUNC = 10;
    const FETCH_GROUP = 65536;
    const FETCH_INTO = 9;
    const FETCH_LAZY = 1;
    const FETCH_NAMED = 11;
    const FETCH_NUM = 3;
    /**
     * User-provided configuration
     * @var array
     */
    protected $_config = array();

    /**
     * Database connection
     * @var object|resource|null
     */
    protected $_connection = null;

    /**
     * Specifies whether the adapter automatically quotes identifiers.
     * If true, most SQL generated by A_Sql classes applies
     * identifier quoting automatically.
     * If false, developer must quote identifiers themselves
     * by calling quoteIdentifier().
     *
     * @var bool
     */
    protected $_autoQuoteIdentifiers = true;

    /**
     * Fetch mode
     *
     * @var integer
     */
    protected $_fetchMode = self::FETCH_ASSOC;

    /**
     * Constructor.
     *
     * $config is an array of key/value pairs or an instance of A_Config
     * containing configuration options.  These options are common to most adapters:
     *
     * dbname         => (string) The name of the database to user
     * username       => (string) Connect to the database as this username.
     * password       => (string) Password associated with the username.
     * host           => (string) What host to connect to, defaults to localhost
     *
     * @param  array|A_Config $config
     * @throws A_Db_Exception
     */
    public function __construct($config) {
        if (!is_array($config)) {
            if ($config instanceof A_Config) {
                $config = $config->toArray();
            } else {
                require_once 'A/Db/Exception.php';
                throw new A_Db_Exception('Adapter parameters must be in an array or a A_Config object');
            }
        }

        $this->_checkRequiredOptions($config);
        $this->_config  = $config; 
    }

    /**
     * Check for config options that are mandatory.
     * Throw exceptions if any are missing.
     *
     * @param array $config
     * @throws A_Db_Exception
     */
    protected function _checkRequiredOptions(array $config)
    {
        if (! array_key_exists('dbname', $config)) {
            require_once 'A/Db/Exception.php';
            throw new A_Db_Exception("Configuration array must have a key for 'dbname' that names the database instance");
        }

        if (! array_key_exists('password', $config)) {
           require_once 'A/Db/Exception.php';
           throw new A_Db_Exception("Configuration array must have a key for 'password' for login credentials");
        }

        if (! array_key_exists('username', $config)) {
            require_once 'A/Db/Exception.php';
            throw new A_Db_Exception("Configuration array must have a key for 'username' for login credentials");
        }
    }

    /**
     * Returns the underlying database connection object or resource.
     * If not presently connected, this initiates the connection.
     *
     * @return object|resource|null
     */
    public function getConnection()
    {
        $this->_connect();
        return $this->_connection;
    }

    /**
     * Returns the configuration variables in this adapter.
     *
     * @return array
     */
    public function getConfig(){
        return $this->_config;
    }

    /**
     * Prepares and executes an SQL statement with bound data.
     *
     * @param  mixed  $sql  The SQL statement with placeholders.
     *                      May be a string or A_Sql_Select.
     * @param  mixed  $bind An array of data to bind to the placeholders.
     * @return A_Sql_Statement
     */
    public function query($sql, $bind = array()) {
        // connect to the database if needed
        $this->_connect();

        // is the $sql a A_Sql_Select object?
        if ($sql instanceof A_Sql_Select) {
            $sql = $sql->__toString();
        }

        // make sure $bind to an array;
        // don't use (array) typecasting because
        // because $bind may be a A_Sql_Expression object
        if (!is_array($bind)) {
            $bind = array($bind);
        }

        // prepare and execute the statement with profiling
        $stmt = $this->prepare($sql);
        $stmt->execute($bind);

        // return the results embedded in the prepared statement object
        $stmt->setFetchMode($this->_fetchMode);
        return $stmt;
    }

    /**
     * Leave autocommit mode and begin a transaction.
     *
     * @return bool True
     */
    public function beginTransaction() {
        $this->_connect();
        $this->_beginTransaction();
        return true;
    }

    /**
     * Commit a transaction and return to autocommit mode.
     *
     * @return bool True
     */
    public function commit() {
        $this->_connect();
        $this->_commit();
        return true;
    }

    /**
     * Roll back a transaction and return to autocommit mode.
     *
     * @return bool True
     */
    public function rollBack() {
        $this->_connect();
        $this->_rollBack();
        return true;
    }

    /**
     * Inserts a table row with specified data.
     *
     * @param mixed $table The table to insert data into.
     * @param array $bind Column-value pairs.
     * @return int The number of affected rows.
     */
    public function insert($table, array $bind)
    {
        // extract and quote col names from the array keys
        $cols = array();
        $vals = array();
        foreach ($bind as $col => $val) {
            $cols[] = $this->quoteIdentifier($col, true);
            if ($val instanceof A_Sql_Expression) {
                $vals[] = $val->__toString();
                unset($bind[$col]);
            } else {
                $vals[] = '?';
            }
        }

        // build the statement
        $sql = "INSERT INTO "
             . $this->quoteIdentifier($table, true)
             . ' (' . implode(', ', $cols) . ') '
             . 'VALUES (' . implode(', ', $vals) . ')';

        // execute the statement and return the number of affected rows
        $stmt = $this->query($sql, array_values($bind));
        $result = $stmt->rowCount();
        return $result;
    }

    /**
     * Updates table rows with specified data based on a WHERE clause.
     *
     * @param  mixed        $table The table to update.
     * @param  array        $bind  Column-value pairs.
     * @param  mixed        $where UPDATE WHERE clause(s).
     * @return int          The number of affected rows.
     */
    public function update($table, array $bind, $where = '')
    {
        /**
         * Build "col = ?" pairs for the statement,
         * except for A_Sql_Expression which is treated literally.
         */
        $set = array();
        foreach ($bind as $col => $val) {
            if ($val instanceof A_Sql_Expression) {
                $val = $val->__toString();
                unset($bind[$col]);
            } else {
                $val = '?';
            }
            $set[] = $this->quoteIdentifier($col, true) . ' = ' . $val;
        }

        $where = $this->_whereExpr($where);

        /**
         * Build the UPDATE statement
         */
        $sql = "UPDATE "
             . $this->quoteIdentifier($table, true)
             . ' SET ' . implode(', ', $set)
             . (($where) ? " WHERE $where" : '');

        /**
         * Execute the statement and return the number of affected rows
         */
        $stmt = $this->query($sql, array_values($bind));
        $result = $stmt->rowCount();
        return $result;
    }

    /**
     * Deletes table rows based on a WHERE clause.
     *
     * @param  mixed        $table The table to update.
     * @param  mixed        $where DELETE WHERE clause(s).
     * @return int          The number of affected rows.
     */
    public function delete($table, $where = '')
    {
        $where = $this->_whereExpr($where);

        /**
         * Build the DELETE statement
         */
        $sql = "DELETE FROM "
             . $this->quoteIdentifier($table, true)
             . (($where) ? " WHERE $where" : '');

        /**
         * Execute the statement and return the number of affected rows
         */
        $stmt = $this->query($sql);
        $result = $stmt->rowCount();
        return $result;
    }

    /**
     * Convert an array, string, or A_Sql_Where object
     * into a string to put in a WHERE clause.
     *
     * @param mixed $where
     * @return string
     */
    protected function _whereExpr($where)
    {
        if (empty($where)) {
            return $where;
        }
        if (!is_array($where)) {
            $where = array($where);
        }
        foreach ($where as &$term) {
            if ($term instanceof A_Sql_Where) {
                $term = $term->__toString();
            }
            $term = '(' . $term . ')';
        }
        $where = implode(' AND ', $where);
        return $where;
    }

    /**
     * Creates and returns a new A_Sql_Select object for this adapter.
     *
     * @return A_Sql_Select
     */
    public function select()
    {
        return new A_Sql_Select($this);
    }

    /**
     * Get the fetch mode.
     *
     * @return int
     */
    public function getFetchMode() {
        return $this->_fetchMode;
    }

    /**
     * Fetches all SQL result rows as a sequential array.
     * Uses the current fetchMode for the adapter.
     *
     * @param string|A_Sql_Select $sql  An SQL SELECT statement.
     * @param mixed                 $bind Data to bind into SELECT placeholders.
     * @param mixed                 $fetchMode Override current fetch mode.
     * @return array
     */
    public function fetchAll($sql, $bind = array(), $fetchMode = null)
    {
        if ($fetchMode === null) {
            $fetchMode = $this->_fetchMode;
        }
        $stmt = $this->query($sql, $bind);
        $result = $stmt->fetchAll($fetchMode);
        return $result;
    }

    /**
     * Fetches the first row of the SQL result.
     * Uses the current fetchMode for the adapter.
     *
     * @param string|A_Sql_Select $sql An SQL SELECT statement.
     * @param mixed $bind Data to bind into SELECT placeholders.
     * @param mixed                 $fetchMode Override current fetch mode.
     * @return array
     */
    public function fetchRow($sql, $bind = array(), $fetchMode = null)
    {
        if ($fetchMode === null) {
            $fetchMode = $this->_fetchMode;
        }
        $stmt = $this->query($sql, $bind);
        $result = $stmt->fetch($fetchMode);
        $stmt->closeCursor();
        return $result;
    }

    /**
     * Quote a raw string.
     *
     * @param string $value     Raw string
     * @return string           Quoted string
     */
    protected function _quote($value) {
        if (is_int($value) || is_float($value)) {
            return $value;
        }
        return "'" . addcslashes($value, "\000\n\r\\'\"\032") . "'";
    }

    /**
     * Safely quotes a value for an SQL statement.
     *
     * If an array is passed as the value, the array values are quoted
     * and then returned as a comma-separated string.
     *
     * @return mixed An SQL-safe quoted value (or string of separated values).
     */
    public function quote($value)
    {
        $this->_connect();

        if ($value instanceof A_Sql_Expression) {
            return $value->__toString();
        }

        if (is_array($value)) {
            foreach ($value as &$val) {
                $val = $this->quote($val);
            }
            return implode(', ', $value);
        }

        return $this->_quote($value);
    }

    /**
     * Quotes a value and places into a piece of text at a placeholder.
     *
     * The placeholder is a question-mark; all placeholders will be replaced
     * with the quoted value.   For example:
     *
     * <code>
     * $text = "WHERE date < ?";
     * $date = "2005-01-02";
     * $safe = $sql->quoteInto($text, $date);
     * // $safe = "WHERE date < '2005-01-02'"
     * </code>
     *
     * @param string  $text  The text with a placeholder.
     * @param mixed   $value The value to quote.
     * @param string  $type  OPTIONAL SQL datatype
     * @param integer $count OPTIONAL count of placeholders to replace
     * @return string An SQL-safe quoted value placed into the orignal text.
     */
    public function quoteInto($text, $value, $type = null, $count = null) {
        if ($count === null) {
            return str_replace('?', $this->quote($value, $type), $text);
        } else {
            while ($count > 0) {
                if (strpos($text, '?') != false) {
                    $text = substr_replace($text, $this->quote($value), strpos($text, '?'), 1);
                }
                --$count;
            }
            return $text;
        }
    }

    /**
     * Quotes an identifier.
     *
     * Accepts a string representing a qualified indentifier. For Example:
     * <code>
     * $adapter->quoteIdentifier('myschema.mytable')
     * </code>
     * Returns: "myschema"."mytable"
     *
     * Or, an array of one or more identifiers that may form a qualified identifier:
     * <code>
     * $adapter->quoteIdentifier(array('myschema','my.table'))
     * </code>
     * Returns: "myschema"."my.table"
     *
     * The actual quote character surrounding the identifiers may vary depending on
     * the adapter.
     *
     * @param string|array|A_Sql_Expression $ident The identifier.
     * @param boolean $auto If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @return string The quoted identifier.
     */
    public function quoteIdentifier($ident, $auto=false) {
        return $this->_quoteIdentifierAs($ident, null, $auto);
    }

    /**
     * Quote a column identifier and alias.
     *
     * @param string|array|A_Sql_Expression $ident The identifier or expression.
     * @param string $alias An alias for the column.
     * @param boolean $auto If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @return string The quoted identifier and alias.
     */
    public function quoteColumnAs($ident, $alias, $auto=false) {
        return $this->_quoteIdentifierAs($ident, $alias, $auto);
    }

    /**
     * Quote a table identifier and alias.
     *
     * @param string|array|A_Sql_Expression $ident The identifier or expression.
     * @param string $alias An alias for the table.
     * @param boolean $auto If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @return string The quoted identifier and alias.
     */
    public function quoteTableAs($ident, $alias = null, $auto=false) {
        return $this->_quoteIdentifierAs($ident, $alias, $auto);
    }

    /**
     * Quote an identifier and an optional alias.
     *
     * @param string|array|A_Sql_Expression $ident The identifier or expression.
     * @param string $alias An optional alias.
     * @param string $as The string to add between the identifier/expression and the alias.
     * @param boolean $auto If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @return string The quoted identifier and alias.
     */
    protected function _quoteIdentifierAs($ident, $alias = null, $auto = false, $as = ' AS ')
    {
        if ($ident instanceof A_Sql_Expression) {
            $quoted = $ident->__toString();
        } elseif ($ident instanceof A_Sql_Select) {
            $quoted = '(' . $ident->__toString() . ')';
        } else {
            if (is_string($ident)) {
                $ident = explode('.', $ident);
            }
            if (is_array($ident)) {
                $segments = array();
                foreach ($ident as $segment) {
                    if ($segment instanceof A_Sql_Expression) {
                        $segments[] = $segment->__toString();
                    } else {
                        $segments[] = $this->_quoteIdentifier($segment, $auto);
                    }
                }
                if ($alias !== null && end($ident) == $alias) {
                    $alias = null;
                }
                $quoted = implode('.', $segments);
            } else {
                $quoted = $this->_quoteIdentifier($ident, $auto);
            }
        }
        if ($alias !== null) {
            $quoted .= $as . $this->_quoteIdentifier($alias, $auto);
        }
        return $quoted;
    }

    /**
     * Quote an identifier.
     *
     * @param  string $value The identifier or expression.
     * @param boolean $auto If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @return string        The quoted identifier and alias.
     */
    protected function _quoteIdentifier($value, $auto=false) {
        if ($auto === false || $this->_autoQuoteIdentifiers === true) {
            $q = $this->getQuoteIdentifierSymbol();
            return ($q . str_replace("$q", "$q$q", $value) . $q);
        }
        return $value;
    }

    /**
     * Returns the symbol the adapter uses for delimited identifiers.
     *
     * @return string
     */
    public function getQuoteIdentifierSymbol() {
        return '"';
    }

    /**
     * Return the most recent value from the specified sequence in the database.
     * This is supported only on RDBMS brands that support sequences
     * (e.g. Oracle, PostgreSQL, DB2).  Other RDBMS brands return null.
     *
     * @param string $sequenceName
     * @return string
     */
    public function lastSequenceId($sequenceName) {
        return null;
    }

    /**
     * Generate a new value from the specified sequence in the database, and return it.
     * This is supported only on RDBMS brands that support sequences
     * (e.g. Oracle, PostgreSQL, DB2).  Other RDBMS brands return null.
     *
     * @param string $sequenceName
     * @return string
     */
    public function nextSequenceId($sequenceName) {
        return null;
    }
	
    /**
     * Get the fetch mode.
     *
     * @return int
     */
    public function getFetchMode() {
        return $this->_fetchMode;
    }
    /**
     * Abstract Methods
     */

    /**
     * Returns a list of the tables in the database.
     *
     * @return array
     */
    abstract public function listTables();

    /**
     * Returns the column descriptions for a table.
     *
     * The return value is an associative array keyed by the column name,
     * as returned by the RDBMS.
     *
     * The value of each array element is an associative array
     * with the following keys:
     *
     * SCHEMA_NAME => string; name of database or schema
     * TABLE_NAME  => string;
     * COLUMN_NAME => string; column name
     * COLUMN_POSITION => number; ordinal position of column in table
     * DATA_TYPE   => string; SQL datatype name of column
     * DEFAULT     => string; default expression of column, null if none
     * NULLABLE    => boolean; true if column can have nulls
     * LENGTH      => number; length of CHAR/VARCHAR
     * SCALE       => number; scale of NUMERIC/DECIMAL
     * PRECISION   => number; precision of NUMERIC/DECIMAL
     * UNSIGNED    => boolean; unsigned property of an integer type
     * PRIMARY     => boolean; true if column is part of the primary key
     * PRIMARY_POSITION => integer; position of column in primary key
     *
     * @param string $tableName
     * @param string $schemaName OPTIONAL
     * @return array
     */
    abstract public function describeTable($tableName, $schemaName = null);

    /**
     * Creates a connection to the database.
     *
     * @return void
     */
    abstract protected function _connect();

    /**
     * Force the connection to close.
     *
     * @return void
     */
    abstract public function closeConnection();

    /**
     * Prepare a statement and return a PDOStatement-like object.
     *
     * @param string|A_Sql_Select $sql SQL query
     * @return A_Db_Statment|PDOStatement
     */
    abstract public function prepare($sql);

    /**
     * Gets the last ID generated automatically by an IDENTITY/AUTOINCREMENT column.
     *
     * As a convention, on RDBMS brands that support sequences
     * (e.g. Oracle, PostgreSQL, DB2), this method forms the name of a sequence
     * from the arguments and returns the last id generated by that sequence.
     * On RDBMS brands that support IDENTITY/AUTOINCREMENT columns, this method
     * returns the last value generated for such a column, and the table name
     * argument is disregarded.
     *
     * @param string $tableName   OPTIONAL Name of table.
     * @param string $primaryKey  OPTIONAL Name of primary key column.
     * @return string
     */
    abstract public function lastInsertId($tableName = null, $primaryKey = null);

    /**
     * Begin a transaction.
     */
    abstract protected function _beginTransaction();

    /**
     * Commit a transaction.
     */
    abstract protected function _commit();

    /**
     * Roll-back a transaction.
     */
    abstract protected function _rollBack();

    /**
     * Adds an adapter-specific LIMIT clause to the SELECT statement.
     *
     * @param mixed $sql
     * @param integer $count
     * @param integer $offset
     * @return string
     */
    abstract public function limit($sql, $count, $offset = 0);
	
    /**
     * Set the fetch mode.
     *
     * @param integer $mode
     * @return void
     * @throws A_Db_Exception
     */
    abstract public function setFetchMode($mode);
}