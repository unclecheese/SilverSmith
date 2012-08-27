<?php


/**
 * Defines a set of PageType nodes
 *
 * Bedrock is a PHP library built by Aaron Carlino that turns YAML in to traversable objects.
 * It is sensitive to other classes that contain the prefix "Bedrock"
 * https://github.com/unclecheese/bedrock
 *
 * @package SilverSmith
 * @author Aaron Carlino <unclecheese@leftandmain.com>
 */
class BedrockPageTypes extends SilverSmithNode {


	/**
	 * @var string The iterator class to use when looping through this node
	 */
    protected $iteratorClass = "BedrockPageTypes_Iterator";
    
    
}




/**
 * Iterator class for PageTypes
 * @package SilverSmith
 * @author Aaron Carlino <unclecheese@leftandmain.com>
 */
class BedrockPageTypes_Iterator extends BedrockNode_Iterator {



	/**
	 * Creates a new node when iterating through the set
	 *
	 * @param string $key The name of the node
	 * @param The source of the node, e.g. an array representing the node's YAML
	 * @param The dot-syntax path to the node, e.g. PageTypes.MyPage
	 * @return BedrockPageType
	 */	
    protected function createNode($key, $source = null, $path = null) {
        return new BedrockPageType($key, current($this->source), "{$this->path}.{$key}", $this->list);
    }
    
}

