<?php

class admin extends A_Controller_Action {

	function run($locator) {
	//	$this->load()->response()->view(); For now keep it simple like below
		
		$content = '
<html>
<body>
	<h2>Admin Page Action</h2>
	<p><a href="/examples/login/">home</a> | <a href="signin/">sign in</a></p>
</body>
</html>
';
		$response = $locator->get('Response');
		$response->setContent($content);
		
	}

}