<?php

class BedrockField extends SilverSmithNode {
    public function getName()
    {
        return $this->key;
    }
    
    public function getParentName()
    {
        $parts = explode('.', $this->getPath());
        return $parts[sizeof($parts) - 3];
    }
    
    
    public function getVar()
    {
        return '$' . strtolower($this->key);
    }
    
    
    public function getNamespace()
    {
        return $this->getParentName();
    }
    
    
    public function getEntity()
    {
        return SilverSmithUtil::generate_unique_i18n_entity($this->getLabel(), $this->getNamespace());
    }
    
    
    public function getAutoFill()
    {
        if (!$content = $this->getConfigVar('AutoFill')) {
            $content = SilverSmithDefaults::get('AutoFill');
        }
        $template = new BedrockTemplate($content);
        $template->bind($this);
        return $template->render();
    }
    
    
    public function getLabel()
    {
        if ($this->get('Label'))
            return $this->get('Label');
        $fieldName = $this->key;
        if (strpos($fieldName, '.') !== false) {
            $parts = explode('.', $fieldName);
            $label = $parts[count($parts) - 2] . ' ' . $parts[count($parts) - 1];
        } else {
            $label = $fieldName;
        }
        $label = preg_replace("/([a-z]+)([A-Z])/", "$1 $2", $label);
        
        return $label;
    }


    public function getEscapedLabel() {
        return addslashes($this->getLabel());
    }
    
    
    public function getConfig()
    {
        $fields = SilverSmith::get_field_manifest();
        if (isset($fields[$this->getCMSField()])) {            
            $config = $fields[$this->getCMSField()];
            return $config;
        }
        return new BedrockYAML(null);
    }
    
    
    public function getConfigVar($var)
    {
        $result = $this->getConfig()->get($var);
        if (substr($result, 0, 2) == "->") {
            $func = "get" . substr($result, 2);
            return $this->$func();
        }
        return $result;
    }
    
    
    public function getDBField()
    {
        if ($this->get('DBField')) {
            return $this->get('DBField');
        }
        return $this->getConfigVar('DBField');        
    }
    
    
    public function getInstantiation()
    {
        if ((!$yml = $this->getConfig()) || (!$content = $yml->getInstantiate())) {
            $content = SilverSmithDefaults::get('InstantiateField');
        }
        $template = new BedrockTemplate(SilverSmithUtil::tabify(rtrim($content)));        
        $template->bind($this);
        $inst = $template->render();
        $up   = $this->getUpdate();
        if ($up && !empty($up)) {
            return $this->getVar() . " = " . $inst;
        }
        return $inst;
    }
    
    
    public function getUpdate()
    {
        if ($yml = $this->getConfig()) {
            if ($content = $yml->getUpdate()) {
                $template = new BedrockTemplate(SilverSmithUtil::tabify($content));
                $template->bind($this);
                return $template->render();
            }
        }
    }
    
    public function getHolder()
    {
        if ($parent = $this->getParentNode()) {
            if ($parent->key == "Fields") {
                if ($grandparent = $parent->getParentNode()) {
                    return $grandparent;
                }
            }
        }
        return false;
    }
    
    
    public function getCategory()
    {
        if ($holder = $this->getHolder()) {
            return $holder->getParentNode();
        }
        return false;
    }
    
    
    public function getIsPage()
    {
        if ($this->getHolder()->getKey() == "SiteConfig")
            return false;
        $parts = explode('.', $this->path);
        return ($parts[sizeof($parts) - 4] == "PageTypes");
    }
    
    
    public function getIsModelAdmin()
    {
        if ($this->getHolder()->getKey() == "SiteConfig")
            return true;
        $parts = explode('.', $this->path);
        return reset($parts) == "Components";
    }
    
    public function getTab()
    {
        $t = $this->get('Tab');
        if($t) {
	        if(substr($t, 0, 5) == "Root.") {
	        	return $t;
	        }
	        return "Root.{$t}";
    	}
    	return "Root.Main";
    }


    public function getBefore() {
        if(!$this->get('Tab') && $this->getIsPage()) {
            return "Content";
        }
        return false;
    }



    public function getEnumField() {
        if($this->get('Map')) {
            return 'Enum("'.implode(',',$this->get('Map')->toArray()).'")';            
        }
        return 'Enum("")';
    }

    
}

