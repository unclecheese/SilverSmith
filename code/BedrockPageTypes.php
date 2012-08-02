<?php


class BedrockPageTypes extends SilverSmithNode {
    protected $iteratorClass = "BedrockPageTypes_Iterator";
    
    
}



class BedrockPageTypes_Iterator extends BedrockNode_Iterator {
    protected function createNode($key, $source = null, $path = null) {
        return new BedrockPageType($key, current($this->source), "{$this->path}.{$key}", $this->list);
    }
    
}

