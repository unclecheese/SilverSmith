<?php

/**
 * Defines a set of components, as described under a PageType node, for instance	
 *
 * Bedrock is a PHP library built by Aaron Carlino that turns YAML in to traversable objects.
 * It is sensitive to other classes that contain the prefix "Bedrock"
 * https://github.com/unclecheese/bedrock
 *
 * @package SilverSmith
 * @author Aaron Carlino <unclecheese@leftandmain.com>
 */
class BedrockComponents extends SilverSmithNode {

	
	/**
	 * @var string The class that the {@link IteratorAggregate} will use
	 */
    protected $iteratorClass = "BedrockComponents_Iterator";
    
    
}




/**
 * Iterator class for Components	
 * @package SilverSmith
 * @author Aaron Carlino <unclecheese@leftandmain.com>
 */
class BedrockComponents_Iterator extends BedrockNode_Iterator {


	/**
	 * Creates a new node when iterating through the set
	 *
	 * @param string $key The name of the node
	 * @param The source of the node, e.g. an array representing the node's YAML
	 * @param The dot-syntax path to the node, e.g. PageTypes.MyPage.Components.SomeComponent
	 * @return BedrockComponent
	 */
    protected function createNode($key, $source = null, $path = null) {
        return new BedrockComponent($key, current($this->source), "{$this->path}.{$key}", $this->list);
    }
    
}
