<?php


class SilverSmithNode extends BedrockNode {
	
	public function get($setting) {
		$result = parent::get($setting);
		if(!$result) {
			return SilverSmithDefaults::get($setting);
		}
		return $result;
	}
}