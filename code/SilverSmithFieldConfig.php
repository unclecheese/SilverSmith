<?php

abstract class SilverSmithFieldConfig {


	protected $fieldNode;



	public function setField($node) {
		$this->fieldNode = $node;
	}



	public function getBaseName() {
		return str_replace("_Config","", get_class($this));
	}


	public function getDBField() {
		return "Varchar(255)";
	}


	public function getHasOne() {
		return false;
	}



	public function getAliases() {
		return array($this->getBaseName());
	}



	public function getLabel() {
		return $this->getBaseName();
	}



	protected function renderTemplate($name) {
		$path = SilverSmith::get_script_dir()."/code/lib/fields/templates/{$name}";
		if(file_exists($path)) {
			$template = new BedrockTemplate(file_get_contents($path));
			$template->bind($this->fieldNode);
			return $template->render();		
		}
		return false;
	}




	public function getInstantiate() {
		if($output = $this->renderTemplate("instantiate/{$this->getBaseName()}.bedrock")) {
			return $output;
		}
		return $this->renderTemplate("instantiate/_default.bedrock");
	}




	public function getAutoFill() {
		if($output = $this->renderTemplate("autofill/{$this->getBaseName()}.bedrock")) {
			return $ouput;
		}
		return $this->renderTemplate("autofill/_default.bedrock");

	}




	public function getUpdate() {
		return $this->renderTemplate("update/{$this->getBaseName()}.bedrock");
	}



}