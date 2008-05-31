<?php
include_once 'A/Html/Tag.php';

class A_Html_P extends A_Html_Tag {

	/*
	 * name=string, value=string
	 */
	public function render($attr=array(), $str='') {
		parent::mergeAttr($attr);
		if (!$str && isset($attr['value'])) {
			$str = $attr['value'];
			parent::removeAttr($attr, 'value');
		}
		parent::removeAttr($attr, 'type');
		return A_Html_Tag::render('p', $attr, $str);
	}

}