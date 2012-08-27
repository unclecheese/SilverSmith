<?php


/**
 * Defines a child node of the Fields node in a project defintion YAML file		
 *
 * Bedrock is a PHP library built by Aaron Carlino that turns YAML in to traversable objects.
 * It is sensitive to other classes that contain the prefix "Bedrock"
 * https://github.com/unclecheese/bedrock
 * 
 * @package SilverSmith
 * @author Aaron Carlino <unclecheese@leftandmain.com>
 */
class BedrockField extends SilverSmithNode {
    
    
    /**
     * Gets the name of the field
     *
     * @return string
     */
    public function getName() {
        return $this->key;
    }
    
    
    
    /**
     * Gets the name of the class that contains this field
     *
     * @return string
     */
    public function getParentName() {
        $parts = explode('.', $this->getPath());
        return $parts[sizeof($parts) - 3];
    }
    
    
 
 
    /**
     * Gets a PHP variable to represent this formfield
     *
     * @return string
     */
    public function getVar() {
        return '$' . strtolower($this->key);
    }
    
    
    
    
    /**
     * Gets a namespace for this form field (for _t() functions)
     *
     * @return string
     */
    public function getNamespace() {
        return $this->getParentName();
    }
    
    
    
    
    /**
     * Generates an entity name for _t() functions
     *
     * @return string
     */
    public function getEntity() {
        return SilverSmithUtil::generate_unique_i18n_entity($this->getLabel(), $this->getNamespace());
    }
    
    


    /**
     * Gets the "autofill" content for template generation, e.g. dumps out the field
     * name and a template variable for it.
     *
     * @return string
     */
    public function getAutoFill() {
        if (!$content = $this->getConfigVar('AutoFill')) {
            $content = SilverSmithDefaults::get('AutoFill');
        }
        $template = new BedrockTemplate($content);
        $template->bind($this);
        return $template->render();
    }
    
    
    
    
    /**
     * Gets a label for the form field
     *
     * @return string
     */
    public function getLabel() {
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




    /**
     * Gets a label for the form field and escapes any quotes
     *
     * @return string
     */
    public function getEscapedLabel() {
        return addslashes($this->getLabel());
    }
    
    
    
    
    /**
     * Gets the configuration for this form field as defined in its YAML file, e.g.
     * checkbox.yml
     *
     * @return BedrockNode
     */
    public function getConfig() {
        $fields = SilverSmith::get_field_manifest();
        if (isset($fields[$this->getCMSField()])) {            
            $config = $fields[$this->getCMSField()];
            return $config;
        }
        return new BedrockYAML(null);
    }
    
    
    
    
    /**
     * Gets a given variable from the field configuration
     *
     * @param string The variable name
     * @return mixed
     */
    public function getConfigVar($var) {
        $result = $this->getConfig()->get($var);
        if (substr($result, 0, 2) == "->") {
            $func = "get" . substr($result, 2);
            return $this->$func();
        }
        return $result;
    }
    
    
    
    
    /**
     * Gets the database fieldtype for this field, e.g. Varchar
     *
     * @return string
     */
    public function getDBField() {
        if ($this->get('DBField')) {
            return $this->get('DBField');
        }
        return $this->getConfigVar('DBField');        
    }
    
    
    
    
    /**
     * Gets the code that will instantiate this form field in PHP code
     *
     * @return string
     */
    public function getInstantiation() {
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
    
    
    
    
    
    /**
     * Get the "update" code for the form field, i.e. configuring the FormField object
     * after instantiation
     *
     * @return string
     */
    public function getUpdate() {
        if ($yml = $this->getConfig()) {
            if ($content = $yml->getUpdate()) {
                $template = new BedrockTemplate(SilverSmithUtil::tabify($content));
                $template->bind($this);
                return $template->render();
            }
        }
    }
    
    
    
    
    /**
     * Gets the PageType or Component node that owns this field
     *
     * @return BedrockNode
     */
    public function getHolder() {
        if ($parent = $this->getParentNode()) {
            if ($parent->key == "Fields") {
                if ($grandparent = $parent->getParentNode()) {
                    return $grandparent;
                }
            }
        }
        return false;
    }
    
    
    
    
    /**
     * Gets the type of holder this field belongs to, e.g. "PageType" or "Component"
     *
     * @return string
     */
    public function getCategory() {
        if ($holder = $this->getHolder()) {
            return $holder->getParentNode();
        }
        return false;
    }
    
    
    
    
    /**
     * Determines if this field appears in a SiteTree class
     *
     * @return bool
     */
    public function getIsPage() {
        if ($this->getHolder()->getKey() == "SiteConfig")
            return false;
        $parts = explode('.', $this->path);
        return ($parts[sizeof($parts) - 4] == "PageTypes");
    }
    
    
    
    
    /**
     * Determines if this field belongs to a DataObject class that is standalone,
     * e.g. not managed on a page
     *
     * @return bool
     */
    public function getIsModelAdmin() {
        if ($this->getHolder()->getKey() == "SiteConfig")
            return true;
        $parts = explode('.', $this->path);
        return reset($parts) == "Components";
    }
    


    /**
     * Gets a name of the tab for this field
     *
     * @return string
     */
    public function getTab() {
        $t = $this->get('Tab');
        if($t) {
	        if(substr($t, 0, 5) == "Root.") {
	        	return $t;
	        }
	        return "Root.{$t}";
    	}
    	return "Root.Main";
    }



    
    /**
     * Gets the name of the field that this field comes before in the FieldSet
     *
     * @return string
     */
    public function getBefore() {
        if(!$this->get('Tab') && $this->getIsPage()) {
            return "Content";
        }
        return false;
    }




    /**
     * Takes the Map attribute of this node and builds it into a string representing
     * an Enum fieldtype in a $db array. 
     *
     * @return string
     */
    public function getEnumField() {
        if($this->get('Map')) {
            return 'Enum("'.implode(',',$this->get('Map')->toArray()).'")';            
        }
        return 'Enum("")';
    }

    
}

