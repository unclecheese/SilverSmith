<?php


class ImageField_Config extends SilverSmithFieldConfig {


	public function getDBField() {
		return false;
	}

	

	public function getHasOne() {
		return $this->fieldNode->getImageClass();
	}



	public function getLabel() {
		return "Image upload";
	}


	public function getAliases() {
		return array (
			'Image'
		);
	}

}