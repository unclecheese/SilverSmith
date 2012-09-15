<?php


class OptionsetField_Config extends SilverSmithFieldConfig {

	public function getDBField() {
		if($map = $this->fieldNode->getMap()) {
			return "Enum('".implode(',',$map->toArray())."')";
		}
		return "Enum()";
	}



	public function getLabel() {
		return "Radio buttons";
	}


	public function getAliases() {
		return array(
			'Radios',
			'Radio',
			'Options'
		);
	}
	
}