<?php


class UploadField_Config extends SilverSmithFieldConfig {


	public function getDBField() {
		return false;
	}



	public function getHasOne() {
		return $this->fieldNode->getFileClass();
	}



	public function getLabel() {
		return "File upload";
	}


	public function getAliases() {
		return array (
			'Upload',
			'File'
		);
	}
	
}