<?php

class BedrockFields extends SilverSmithNode
{
    protected $iteratorClass = "BedrockFields_Iterator";
    
    
}

class BedrockFields_Iterator extends BedrockNode_Iterator
{
    protected function createNode($key, $source = null, $path = null)
    {
        return new BedrockField($key, current($this->source), "{$this->path}.{$key}", $this->list);
    }
    
    
}
