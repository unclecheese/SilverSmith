<?php

class DropdownField_Config extends SilverSmithFieldConfig {

	public function getDBField() {
		if($map = $this->fieldNode->getMap()) {
			return "Enum('".implode(',',$map->toArray())."')";
		}
		return "Enum()";
	}


	public function getAliases() {
		return array('Dropdown');
	}

	
}