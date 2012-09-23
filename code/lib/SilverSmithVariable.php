<?php


class SilverSmithVariable extends SilverSmithNode {



	public function render() {
		$template = SilverSmith::get_script_dir()."/code/lib/statics/templates/".self::$template.".bedrock";
		if($contents = @file_get_contents($template)) {
			$t = new BedrockTemplate($contents);
			$source = array (
				'VarName' => self::$var_name,
				'Contents' => $this->source
			);
			$node = new BedrockNode("Root", $source, "Root");
			$t->bind($node);
			return $t->render();
		}
	}

}