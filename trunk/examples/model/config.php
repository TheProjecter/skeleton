<?php
error_reporting(E_ALL);
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . dirname(__FILE__) . '/../../');

function dump($var, $name='') {
//	echo $name . '<pre>' . print_r($var, true) . '</pre>';
echo '<pre>';	var_dump($var);
}
