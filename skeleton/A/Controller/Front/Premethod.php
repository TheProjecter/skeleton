<?php
/*
 * Created on Sep 5, 2007
 *
 * Create object of this class to pass to Front Controller preFilter() method
 * to call the method named $method, if it exists, before action is dispatched.
 * The method may return an A_DL object which will be returned to the Front Controller
 * to short circuit dispatch. Or it may return true and then the $change_action A_DL object
 * is returned. 
 */

class A_Controller_Front_Premethod {

	public function __construct($method, $change_action, $locator) {
		$this->method = $method;
		$this->change_action = $change_action;
		$this->locator = $locator;
	}
	
	public function run($controller) {
		$change_action = null;
		if (method_exists($controller, $this->method)) {
			// pre-execute method if it exists 
			$change_action = $controller->{$this->method}($this->locator);
			if ($change_action && ! is_object($change_action)) {
				$change_action = $this->change_action;
			}
		}
		return $change_action;
	}
	
}
