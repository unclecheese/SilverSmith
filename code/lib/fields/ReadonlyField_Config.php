<?php


class ReadonlyField_Config extends SilverSmithFieldConfig {


	public function getLabel() {
		return "Readonly field";
	}



	public function getAliases() {
		return array(
			'Readonly'
		);
	}
	
}