<?php


class TextareaField_Config extends SilverSmithFieldConfig {


	public function getDBField() {
		return "Text";
	}



	public function getLabel() {
		return "Text (long)";
	}



	public function getAlises() {
		return array (
			'Textarea'
		);
	}

	
}