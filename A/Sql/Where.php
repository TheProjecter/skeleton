<?php
#require_once 'A/Sql/LogicalList.php';
/**
 * Generate SQL WHERE clause
 * 
 * @package A_Sql 
 */

class A_Sql_Where extends A_Sql_LogicalList {
	public function render() {
		return 'WHERE '. parent::render();
	}
}