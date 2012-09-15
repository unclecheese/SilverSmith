<?php


class NumericField_Config extends SilverSmithFieldConfig {


	public function getDBField() {
		return "Int";
	}



	public function getLabel() {
		return "Number";
	}



	public function getAliases() {
		return array (
			'Number',
			'Numeric'
		);
	}
	
}