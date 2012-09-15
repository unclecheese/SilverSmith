<?php


class DefaultField_Config extends SilverSmithFieldConfig {


	public function getInstantiate() {
		return $this->renderTemplate("instantiate/_default.bedrock");
	}




	public function getAutoFill() {
		return $this->renderTemplate("autofill/_default.bedrock");

	}




	public function getUpdate() {
		return false;
	}


}