<?php


class RelationDropdownField_Config extends SilverSmithFieldConfig {


	public function getHasOne() {
		return $this->fieldNode->getMap();
	}



	public function getDBField() {
		return false;
	}



	public function getLabel() {
		return "Relation dropdown";
	}


	public function getAliases() {
		return array (
			'Relation',
			'RelationDropdown'
		);
	}

	
}