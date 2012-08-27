<?php



/**
 * Defines an object representing an Interface node in the SilverSmith project definition file.
 * Interfaces are form fields used to manage component relationships, e.g. $has_many
 *
 * Bedrock is a PHP library built by Aaron Carlino that turns YAML in to traversable objects.
 * It is sensitive to other classes that contain the prefix "Bedrock"
 * https://github.com/unclecheese/bedrock
 *
 * @package SilverSmith
 * @author Aaron Carlino <unclecheese@leftandmain.com>
 */
class BedrockInterface extends SilverSmithNode {


	
	/**
	 * Gets all of the interfaces that are available in this project, including plugins
	 *
	 * @return array
	 */
    public static function get_valid_interfaces() {
        return array_keys(SilverSmith::get_interface_manifest());
    }
    


    
   /**
    * Get the configuration YAML for this interface as defined in a library YAML file, including
    * the code used to instantiate and update the interface
    *
    * @return SilverSmithNode
    */
    public function getConfig() {        
        $interfaces = SilverSmith::get_interface_manifest();
        if (isset($interfaces[$this->getType()])) {
            return $interfaces[$this->getType()];
        }
        return false;
    }
    
    
    
    
    /**
     * Get a variable that will be used to identify this interface in the getCMSFields() code, e.g.
     * $myGrid->setAddTitle("Foo");
     *
     * @return string
     */
    public function getVar() {
        return '$' . strtolower($this->getParentNode()->transform("BedrockComponent")->getName());
    }
    
    
    
    
    /**
     * Gets the heading, or label, for the interface, e.g. "Select the categories for this product"
     *
     * @return string
     */
    public function getHeading() {
        if (!$this->get('Heading')) {
            return FormField::name_to_label($this->getParentNode()->transform("BedrockComponent")->getName());
        }
        return $this->get('Heading');
    }
    
    
    
    
    /**
     * Gets the entity for this interface for the _t() function
     *
     * @return string
     */
    public function getEntity() {
        return SilverSmithUtil::generate_i18n_entity($this->getHeading());
    }
    
    
    
}

