<?php


class LiteralField_Config extends SilverSmithFieldConfig {


	public function getDBField() {
		return false;
	}



	public function getAliases() {
		return array(
			'Literal',
			'Notes'
		);
	}


	public function getAutoFill() {
		return false;
	}
	
}