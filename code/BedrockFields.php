<?php


/**
 * Defines a set of Fields, as described under a PageType or Component node
 *
 * Bedrock is a PHP library built by Aaron Carlino that turns YAML in to traversable objects.
 * It is sensitive to other classes that contain the prefix "Bedrock"
 * https://github.com/unclecheese/bedrock
 *
 * @package SilverSmith
 * @author Aaron Carlino <unclecheese@leftandmain.com>
 */
class BedrockFields extends SilverSmithNode {


	
	/**
	 * @var string The iterator class to assign to nodes when looping through this object
	 */
    protected $iteratorClass = "BedrockFields_Iterator";
    
    
}

class BedrockFields_Iterator extends BedrockNode_Iterator
{


	/**
	 * Creates a new node when iterating through the set
	 *
	 * @param string $key The name of the node
	 * @param The source of the node, e.g. an array representing the node's YAML
	 * @param The dot-syntax path to the node, e.g. PageTypes.MyPage.Fields.Photo
	 * @return BedrockComponent
	 */
    protected function createNode($key, $source = null, $path = null) {
        return new BedrockField($key, current($this->source), "{$this->path}.{$key}", $this->list);
    }
    
    
}
