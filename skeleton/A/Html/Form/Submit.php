<?php
if (! class_exists('A_Html_Tag')) include 'A/Html/Tag.php';

class A_Html_Form_Submit {

	/*
	 * name=string, value=string
	 */
	public function render($attr) {
		$attr['type'] = 'submit';
		return A_Html_Tag::render('input', $attr);
	}

}
