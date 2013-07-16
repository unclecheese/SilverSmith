<?php
/**
 * Defines a child node of the Components node in a project defintion YAML file	
 * @package SilverSmith
 * @author Aaron Carlino <unclecheese@leftandmain.com>
 */
class BedrockComponent extends BedrockDataRecord {

    
	/**
	 * Gets the parent class, e.g. "DataObject" of this component
	 *
	 * @return string
	 */
    public function getParent() {
        if ($this->getDecorator())
            return "DataExtension";
        return $this->get('Parent') ? $this->get('Parent') : "DataObject";
    }
    
    
    
    /**
     * Gets the "holder" class of this component. Used to figure out if the component
     * is defined under a Page, for example	
     *
     * @return string|bool
     */
    public function getHolder() {        
        if ($parent = $this->getParentNode()) {
            if ($parent->key == "Components") {
                if ($grandparent = $parent->getParentNode()) {
                    return $grandparent->transform("BedrockComponent");
                }
            }
        }
        return false;
    }
    
    
    
    /**
     * Wrapper method to get the class name of the component
     *
     * @return string
     */
    public function getClass() {
        return $this->key;
    }
    
    
    
    /**
     * Gets the name of the component relationship, for example as defined in a $has_many
     * relation on a Page
     * 
     * @boolean $pluralize
     * @return string
     */
    public function getName($pluralize = true)
    {
        if (!$this->get('Name')) {
            return $pluralize 
                    ? SilverSmithUtil::pluralize($this->key)
                    : $this->key;
        }
        return $this->get('Name');
    }
    
    
    
    /**
     * Gets the array of the $has_one vars on this component
     *
     * @return BedrockNode
     */
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
    
    
    
    /**
     * Binds the component to a {@link BedrockTemplate} and creates the PHP code that defines the
     * component in SilverStripe
     *
     * @return string
     */
    public function getGeneratedCode()
    {
        $path = SilverSmith::get_script_dir() . "/code/lib/structures/ComponentCode.bedrock";
        if (file_exists($path)) {
            $template = new BedrockTemplate(file_get_contents($path));
            $template->bind($this);
            $code = $template->render();
            return $code;
        }
    }
    
    
    
    /**
     * An accessor method that "instantiates" a component in a getCMSFields() function.
     * Since components themselves aren't formfields, it just talks to the {@link BedrockInterface}
     * of this component.
     *
     * @return string
     */
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
    
    
    
    /**
     * An accessor method to get the interface config without traversing the Interface node
     *
     * @return SilverSmithNode
     */
    public function getInterfaceConfig() {
        if ($i = $this->getInterface()) {
            return $i->getConfig();
        }
        return false;
    }
    
    
    
    
    /**
     * An accessor method to get the "update" code for the getCMSFields() function.
     * Since components themselves are not form fields, this function just talks to the
     * {@link BedrockInterface} defined on this node and gets its "update" code.
     *
     * @return string
     */
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
    
    
    
    /**
     * Determines whether this component is defined as part of a SiteTree object
     *
     * @return bool
     */
    public function getIsOnPage() {
        $parts = explode('.', $this->path);
        return ($parts[sizeof($parts) - 4] == "PageTypes");
    }
    
    
    
    /**
     * Gets the name of the tab for this component as used in getCMSFields()
     *
     * @return string
     */
    public function getTab() {
    	if(!$this->get('Tab')) {
    		return "Root.{$this->getName()}";
    	}
    	if(substr($this->get('Tab'), 0, 5) == "Root.") {
    		return $this->get('Tab');
    	}
    	return "Root.".$this->get('Tab');
    }
    
    
    
    /**
     * Gets the entire node out of the SilverSmith project definition file
     *
     * @return BedrockNode
     */
    public function getDefinition() {
        return SilverSmithProject::get_node($this->key);
    }
    
    
    
    /**
     * Gets the fields that are defined for this component. If empty, all components
     * get a Title field.
     *
     * @return BedrockNode
     */
    public function getDefinedFields() {
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
