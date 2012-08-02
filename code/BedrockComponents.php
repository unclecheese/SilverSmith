<?php

class BedrockComponents extends SilverSmithNode {
    protected $iteratorClass = "BedrockComponents_Iterator";
    
    
}



class BedrockComponents_Iterator extends BedrockNode_Iterator {
    protected function createNode($key, $source = null, $path = null)
    {
        return new BedrockComponent($key, current($this->source), "{$this->path}.{$key}", $this->list);
    }
    
}
