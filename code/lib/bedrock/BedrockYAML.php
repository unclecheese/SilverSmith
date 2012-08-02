<?php
/**
 * Loads a YAML file and parses it into a traversable array.
 *
 * @example
 * <code>
 * Animals:
 *   Mammals:
 *     Dog:
 *       Friendly: true
 *     Bear:
 *       Friendly: false
 *   Fish:
 *     Salmon:
 *       Yummy: true
 * </code>
 *
 * <code>
 *
 *	$yml = new BedrockYML("/path/to/yaml.yml");
 *	var_dump($yml->getAnimals()->getMammals()->getDog()->getFriendly());
 *  // bool(false)
 *  foreach($yml->get('Animals.Mammals') as $node) {
 *		if($node->getFriendly()) {
 *			echo "{$node->getKey()} is friendly!";
 *		}
 *	}
 *
 * </code>
 *
 * @author Aaron Carlino <unclecheese@leftandmain.com>
 * @package Bedrock
 */
class BedrockYAML extends BedrockNode {
	
	
	
	/**
	 * @var string The path to the YAML file
	 */
	protected $ymlPath;
	


	/**
	 * Builds a new traversable YAML file
	 *
	 * @param string $yml The path to the YAML file
	 */
	public function __construct($yml) {
		$this->ymlPath = $yml;
		$source = is_array($yml) ? $yml : (array) Spyc::YAMLLoad($yml);
		parent::__construct("Root", $source, "Root");		
	}
	
	

	/**
	 * Gets a node from the YAML. Can be in Dot.Separated.Syntax
	 *
	 * @param string The name of the node to get
	 * @return BedrockNode
	 */
	public function get($setting) {		
		$current = $this->source;
		$last_key = null;
		foreach(explode('.', $setting) as $key) {
			$last_key = $key;
			if(!isset($current[$key])) {
				return false;
			}
			if(is_array($current[$key]))
				$current = $current[$key];
			else {
				return is_array($current[$key]) ? BedrockNode::create($key, $current[$key], $setting, $this) : $current[$key];			
			}
		}
		return is_array($current) ? BedrockNode::create($last_key, $current, $setting, $this) : $current;	
	}
	
}

