<?php

class BedrockInterface extends SilverSmithNode {
    public static function get_valid_interfaces()
    {
        return array_keys(SilverSmith::get_interface_manifest());
    }
    
    public function getConfig()
    {        
        $interfaces = SilverSmith::get_interface_manifest();
        if (isset($interfaces[$this->getType()])) {
            return $interfaces[$this->getType()];
        }
        return false;
    }
    
    
    public function getVar()
    {
        return '$' . strtolower($this->getParentNode()->transform("BedrockComponent")->getName());
    }
    
    public function getHeading()
    {
        if (!$this->get('Heading')) {
            return FormField::name_to_label($this->getParentNode()->transform("BedrockComponent")->getName());
        }
        return $this->get('Heading');
    }
    
    public function getEntity()
    {
        return SilverSmithUtil::generate_i18n_entity($this->getHeading());
    }
    
    
    
}

