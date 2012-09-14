<?php

class SilverSmithFieldList extends DataExtension {


	public function getTabForField($field) {
		$_this = $this->owner;
		if($_this->hasTabSet()) {
			foreach($_this->fieldByName("Root")->Tabs() as $tab) {
				if($tab->fieldByName($field)) {
					return $tab->getName();
				}
			}
		}
		return false;
	}



	public function getFieldAfter($field) {
		$_this = $this->owner;
		if($_this->hasTabSet()) {
			foreach($_this->fieldByName("Root")->Tabs() as $tab) {
				$tabFields = $tab->Fields();
				foreach($tabFields as $i => $tabField) {
					if($tabField->getName() == $field) {
						return isset($tabFields[$i+1]) ? $tabFields[$i+1]->getName() : false;
					}
				}
			}
		}
		else {
			$dataFields = $_this->toArray();
			foreach($dataFields as $i => $dataField) {
				if($dataField->getName() == $field) {
					return isset($dataFields[$i+1]) ? $dataFields[$i+1]->getName() : false;
				}
			}
		}
		return false;
	}
}