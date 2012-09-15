<?php

class CurrencyField_Config extends SilverSmithFieldConfig {

	public function getDBField() {
		return "Currency";
	}



	public function getAliases() {
		return array (
			'Currency',
			'Money'
		);
	}


	
}