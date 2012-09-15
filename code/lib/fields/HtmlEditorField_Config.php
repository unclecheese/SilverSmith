<?php


class HtmlEditorField_Config extends SilverSmithFieldConfig {


	public function getDBField() {
		return "HTMLText";
	}


	public function getLabel() {
		return "Rich text editor";
	}



	public function getAliases() {
		return array (
			'HtmlEditor',
			'Wysiwyg'
		);
	}

}