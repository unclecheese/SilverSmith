<?php

class BedrockComponent extends BedrockDataRecord {

    
    public function getParent()
    {
        if ($this->getDecorator())
            return "DataExtension";
        return $this->get('Parent') ? $this->get('Parent') : "DataObject";
    }
    
    public function getHolder()
    {
        if ($parent = $this->getParentNode()) {
            if ($parent->key == "Components") {
                if ($grandparent = $parent->getParentNode()) {
                    return $grandparent;
                }
            }
        }
        return false;
    }
    
    public function getClass()
    {
        return $this->key;
    }
    
    public function getName()
    {
        if (!$this->get('Name')) {
            return SilverSmithUtil::pluralize($this->key);
        }
        return $this->get('Name');
    }
    
    
    public function getHasOneVars()
    {
        $v = parent::getHasOneVars();
        $has_one = $v ? $v->getConfiguration() : array ();
        foreach (SilverSmithProject::get_all_nodes() as $node) {
            if ($components = $node->getComponents()) {
                if ($me = $components->get($this->key)) {
                    if ($me->getType() == "many") {
                        $has_one[$node->key] = $node->key;
                    }
                }
            }
        }
        
        return empty($has_one) ? false : new BedrockNode("Root",$has_one,"Root");
    }
    
    
    public function getGeneratedCode()
    {
        $path = SilverSmith::get_script_dir() . "/code/lib/structures/ComponentCode.bedrock";
        if (file_exists($path)) {
            $template = new BedrockTemplate(file_get_contents($path));
            $template->bind($this);
            return $template->render();
        }
    }
    
    
    public function getInstantiation()
    {
        if ($yml = $this->getInterfaceConfig()) {
            if ($content = $yml->getInstantiate()) {
                $template = new BedrockTemplate(SilverSmithUtil::tabify(rtrim($content)));
                $template->bind($this);
                $inst = $template->render();
                $up   = $this->getUpdate();
                if ($up && !empty($up)) {
                    return $this->getInterface()->getVar() . " = " . $inst;
                }
                return $inst;
            }
        }                
        return false;
    }
    
    public function getInterfaceConfig()
    {
        if ($i = $this->getInterface()) {
            return $i->getConfig();
        }
        return false;
    }
    
    
    public function getUpdate()
    {
        if ($yml = $this->getInterfaceConfig()) {
            if ($content = $yml->getUpdate()) {
                $template = new BedrockTemplate(SilverSmithUtil::tabify($content));
                $template->bind($this);
                return $template->render();
            }
        }
    }
    
    
    public function getIsOnPage()
    {
        $parts = explode('.', $this->path);
        return ($parts[sizeof($parts) - 4] == "PageTypes");
    }
    
    
    public function getTab()
    {
    	if(!$this->get('Tab')) {
    		return "Root.{$this->getName()}";
    	}
    	if(substr($this->get('Tab'), 0, 5) == "Root.") {
    		return $this->get('Tab');
    	}
    	return "Root.".$this->get('Tab');
    }
    
    
    public function getDefinition()
    {
        return SilverSmithProject::get_node($this->key);
    }
    
    
    public function getDefinedFields()
    {
        if ($d = $this->getDefinition()) {
            $f = $d->getFields();
            if(!$f) {
                $d->mergeSource(array(
                    'Fields' => array(
                        'Title' => array('CMSField' => 'Text')                        
                    )
                ));
                return $d->getFields();
            }
            return $f;
        }
        return false;
    }
    
    
    
}
