<?php
/**
 * An object that can intelligently traverse the project configuration loaded through the YML file.
 * Using wildcard methods and {@link IteratorAggregate}
 *
 *
 * @author Aaron Carlino <unclecheese@leftandmain.com>
 * @package Bedrock
 */
class BedrockNode implements IteratorAggregate, Countable
{
	

	/**
	 * Determines which class to apply to the current node, in case a custom
	 * class has been built for this node
	 *
	 * @param string $key The name of the node
	 * @return string
	 */
	public static function get_node_class($key) {
		$bedrock_key = "Bedrock".$key;
		return class_exists($bedrock_key) && is_subclass_of($bedrock_key, "BedrockNode") ? $bedrock_key : false;
	}


	
	/**
	 * Instantiates a new node
	 *
	 * @param string $key The current key of the YAML iterator
	 * @param array $source The full array of data reprsenting this YAML node
	 * @param string $path A dot-separated path to this node, e.g. Animals.Mammals.Dogs
	 * @param BedrockNode $topSource The {@link BedrockNode} that traversed to this node, useful for getting
	 *			back up one level.
	 * @return BedrockNode
	 */
	public static function create($key, $source = null, $path = null, $topSource) {
		if(!is_array($source)) return $source;


		$class =  self::get_node_class($key);
		if(!$class) {
			$class = "BedrockNode";
		}
		return new $class($key, $source, $path, $topSource);
	}
	


	/**
	 * @var string The array key that was used to generate the source
	 */
	protected $key = null;
	


	/**
	 * The array of options related to the current setting.
	 *
	 * @var array
	 */
	protected $source = null;



	/**
	 * The {@link BedrockNode} that traversed to this node
	 */
	public $topSource = null;



	
	/**
	 * The path to the current setting, in dot syntax.
	 * Example: Animals.Mammals.Dogs
	 */
	protected $path = null;
	


	/**
	 * @var string The iterator class to use when looping through this object
	 */
	protected $iteratorClass = "BedrockNode_Iterator";
	


	/**
	 * Converts this node to a string
	 *
	 * @return string
	 */
	public function __toString() {		
		return $this->source;
	}



	/**
	 * A wildcard method for trapping "get" functions
	 *
	 * @param string $method The method called
	 * @param array $args The arguments provided to the method.
	 */
	public function __call($method, $args) {
		if(substr($method,0,3) == "get") {
			return $this->get(substr($method,3));
		}
	}
	


	/**
	 * Builds a new BedrockNode object
	 * 
	 * @param string $key The array key that was used to get the source
	 * @param array $source The settings contained in this object
	 * @param string $path The path, in dot syntax, that can be used to get this setting
	 *		
	 */
	public function __construct($key, $source = null, $path = null, $topSource = null) {
		$this->key = $key;
		$this->source = $source;
		$this->path = $path;
	
		$this->topSource = $topSource;
	}
	



	/**
	 * Gets a member of the {@link $source} array. If $val returns an array,
	 * it returns a new BedrockNode object with that source. This is useful for 
	 * daisy-chaining to traverse the configuration.
	 *
	 * If $val returns a string, return the string.
	 *
	 * @param string $val The array key to check in {@link $source}
	 * @return mixed
	 */	 
	public function get($val) {
		if(is_array($this->source) && isset($this->source[$val])) {
			$v = $this->source[$val];
			if(is_array($v)) {
				return BedrockNode::create($val, $v, $this->path.".".$val, $this->topSource);
			}
			return $v;
		}
		return false;
	}



	/**
	 * Returns the path to find this setting in dot syntax
	 *
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}
		


	/**
	 * Gets the key (name) of this node
	 *
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}



	/**
	 * Necessary for allowing BedrockNode objects to be traversed in a loop
	 *
	 * @return BedrockNode_Iterator
	 */
	public function getIterator() {
		$class = $this->iteratorClass;
		return new $class($this);
	}



	/**
	 * Dumps the entire YAML as an array
	 *
	 * @return array
	 */
	public function getConfiguration() {
		return $this->source;
	}


	/**
	 * Gets the name of the node that owns this node
	 *
	 * @return string
	 */
	public function getParentKey() {
		$parts = explode(".",$this->getPath());
		return $parts[sizeof($parts)-2];
	}



	/**
	 * Gets the name of the node that owns this node
	 * and instantiates a new {@link BedrockNode} object for it
	 *
	 * @return BedrockNode
	 */
	public function getParentNode() {
		$parts = explode('.',$this->path);
		array_pop($parts);
		if(sizeof($parts) > 0) {			
			return $this->topSource->get(implode('.', $parts));
		}
		return $this->topSource;	

	}

	

	
	/**
	 * Returns an array instead of a BedrockNode object.
	 * Ex. $myYAML->getAnimals()->getMammals()->toArray();
	 *
	 * @return array
	 */	
	public function toArray() {
		return is_array($this->source) ? $this->source : array($this->source);
	}
	
	

	/**
	 * If the source is a sequential list, it can be rendered as comma, separated, values.
	 * @return string
	 */
	public function toCSV() {
		return implode(', ', $this->toArray());
	}


	
	/**
	 * Merge two BedrockNode objects into one.
	 *
	 * @param BedrockNode $b The BedrockNode to merge into this one.
	 */
	public function merge(BedrockNode $b) {
		foreach($b->toArray() as $k => $v)
			$this->source[$k] = $v;
	}
	

	/**
	 * Gets the number of members in the current source
	 *
	 * @return int
	 */
	public function size() {
		return sizeof($this->source);
	}
	
	

	/**
	 * Transforms this node into a new {@link BedrockNode} subclass
	 *
	 * @param string $new_class The destination class
	 * @return BedrockNode
	 */
	public function transform($new_class) {
		return new $new_class($this->key, $this->source, $this->path, $this->topSource);
	}



	/**
	 * Gets the first member of the source, if an array
	 *
	 * @return mixed
	 */
	public function first() {
		if(sizeof($this->source) > 0) {
			return $this->source[0];
		}
		return false;
	}



	/**
	 * Gets the last member of the source, if an array
	 *
	 * @return mixed
	 */
	public function last() {
		if(sizeof($this->source) > 0) {
			return $this->source[sizeof($this->source)-1];
		}
		return false;		
	}



	/**
	 * Necessary for the {@link Countable} interface. Provides
	 * PHP a way of counting the object as if an array
	 *
	 * @return int
	 */
	public function count() {
		return sizeof($this->source);
	}
	
}



/**
 * This class informs PHP how to deal with iterating over a BedrockNode object
 * as an array.
 *
 * @package Bedrock
 */
class BedrockNode_Iterator implements Iterator 
{
	
	/**
	 * @var array The key of the array that generated this setting.
	 */
	protected $key;
	


	/**
	 * @var array The source of the {@link BedrockNode} object being iterated.
	 */
	protected $source;
	
	

	/**
	 * @var array The path of the {@link BedrockNode} object being iterated.
	 */
	protected $path;

	
	/**
	 * @var BedrockNode The original YAML that executed this iterator
	 */
	protected $list = null;
	


	/**
	 * @var string The iterator class to use when looping through this object
	 */
	protected $iteratorNodeClass = "BedrockNode";
	
	

	/**
	 * Constructs a new BedrockNode_Iterator using the same path and source
	 * as the {@link BedrockNode} object it will return
	 *
	 * @param array $items The source of the BedrockNode object
	 * @param string $path The path, in dot syntax to the BedrockNode object
	 */
	public function __construct($list) {
		$this->list = $list->topSource;
		$this->key = $list->getKey();
		$this->source = $list->getConfiguration();
		$this->path = $list->getPath();
		
	}
	
	

	/**
	 * Creates a new {@link BedrockNode} for the current position of the iterator
	 *
	 * @param string $key The name of the current node
	 * @param array $source The source data for the entire current node
	 * @param string $path The dot-separated path to this node, e.g. Animals.Mammals.Dogs
	 * @return BedrockNode
	 */
	protected function createNode($key, $source = null, $path = null) {
		$class = BedrockNode::get_node_class($key);
		if(!$class) {
			$class = $this->iteratorNodeClass;
		}
		return new $class($key, current($this->source), "{$this->path}.{$key}", $this->list);		
	}
	


	/**
	 * Return the current object of the iterator.
	 *
	 * @return boolean|BedrockNode
	 */
	public function current() {
		if(!is_array($this->source)) return false;
		$key = key($this->source);
		return $this->createNode($key, current($this->source), "{$this->path}.{$key}", $this->list);
	}
	
	

	/**
	 * Return the key of the current object of the iterator.
	 *
	 * @return mixed
	 */
	public function key() {
		return key($this->source);
	}


	
	/**
	 * Return the next item in this set.
	 *
	 * @return boolean|BedrockNode
	 */
	public function next() {
		if(!is_array($this->source)) return false;
		$key = key($this->source);
		return $this->createNode($key, next($this->source), "{$this->path}.{$key}", $this->list);
	}
	
	

	/**
	 * Rewind the iterator to the beginning of the set.
	 *
	 * @return boolean|BedrockNode The first item in the set.
	 */
	public function rewind() {
		if(!is_array($this->source)) return false;
		$key = key($this->source);
		return $this->createNode($key, reset($this->source), "{$this->path}.{$key}", $this->list);
	}
	
	

	/**
	 * Check the iterator is pointing to a valid item in the set.
	 *
	 * @return boolean
	 */
	public function valid() {
	 	return is_array($this->source) && current($this->source) !== false;
	}
}

