<?php

class index extends A_Controller_Action {
	var $response;

	function __construct($locator) {
		parent::__construct($locator);
		$this->response = $locator->get('Response');
	}

	function run($locator) { 
		$this->load()->response('maincontent')->view();
		//dump($this);
	}

	function bar($locator) {
		$model = $this->load()->model('DaysModel');
		$template = $this->load()->template();
		$template->set('model', $model);
		$this->response->setRenderer($template);
	}
}