<?php

/**
 * Bootstraps all of the dependencies for loading YAML
 *
 * @author Aaron Carlino <unclecheese@leftandmain.com>
 * @package Bedrock
 */
foreach(array("BedrockNode", "BedrockYAML", "Spyc", "BedrockTemplate") as $class) {
	if(!class_exists($class)) {	
		require_once($class.".php");
	}	
}
