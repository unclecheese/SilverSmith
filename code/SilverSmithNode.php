<?php



/**
 * A subclass of {@link BedrockNode} that pipes all failures into {@link SilverSmithDefaults}
 *
 * Bedrock is a PHP library built by Aaron Carlino that turns YAML in to traversable objects.
 * It is sensitive to other classes that contain the prefix "Bedrock"
 * https://github.com/unclecheese/bedrock
 * 	
 * @package SilverSmith
 * @author Aaron Carlino <unclecheese@leftandmain.com>
 */
class SilverSmithNode extends BedrockNode {
	
	
	
	/**
	 * Overloads the {@link BedrockNode::get()} method to pipe failures through {@link SilverSmithDefaults}
	 *
	 * @param string The path to the setting
	 * @return BedrockNode
	 */
	public function get($setting) {
		$result = parent::get($setting);
		if(!$result) {
			return SilverSmithDefaults::get($setting);
		}
		return $result;
	}
}