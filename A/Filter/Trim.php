<?php
/**
 * Filter a string with the trim() function
 * 
 * @package A_Filter 
 */

class A_Filter_Trim {
protected $charset = null;

	public function __construct($charset=null) {
		$this->charset = $charset;
	}

	public function run ($value) {
		return trim($value, $this->charset);
	}

}
