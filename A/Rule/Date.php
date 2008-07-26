<?php
include_once 'A/Rule/Abstract.php';
/**
 * Rule to check for date value
 * 
 * @package A_Validator 
 */

class A_Rule_Date extends A_Rule_Abstract {
	const ERROR = 'A_Rule_Date';
	
    public function __construct($field, $errorMsg) {
      $this->field    = $field;
      $this->errorMsg = $errorMsg;
    }

    public function getErrorMsg() {
      return $this->errorMsg;
    }

    public function isValid($container) {
      return (preg_match("/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/", $container->get($this->field),
              $matches) && checkdate($matches[2], $matches[3], $matches[1]));
    }
}
