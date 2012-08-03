<?php

class SilverSmithSpec
{
    protected static $settings_list;
    
    public static function load($path)
    {
        self::$settings_list = new BedrockYAML($path);
    }
    
    
    public static function get($setting)
    {
        return self::$settings_list->get($setting);
    }
    
    
    public static function get_i18n_map($path)
    {
        $map = array();
        if ($result = self::get($path)) {
            foreach ($result as $name => $setting) {
                $map[$name] = _t('ProjectBuilder.' . strtoupper($name), $setting->getLabel());
            }
        }
        return $map;
    }
    
    
    public function provideI18nEntities()
    {
        $entities   = array();
        $paths      = array(
            'Field.AvailableNodes.DBField',
            'Component.AvailableNodes.Type',
            'Interface'
        );
        $candidates = array();
        foreach ($paths as $path) {
            if ($dropdownValues = self::get($path . '.DropdownValues')) {
                foreach ($dropdownValues as $name => $value) {
                    $entities["ProjectBuilder." . strtoupper($name)] = $value->getLabel();
                }
            }
        }
        return $entities;
    }
    
    
    
}


class SilverSmithSpec_Validator
{
    protected $errors = array();
    
    
    protected $yml;
    
    
    public function __construct($path)
    {
        $this->yml = new BedrockYAML($path);
        $this->validate();
    }
    
    
    public function getErrors()
    {
        return $this->errors;
    }
    
    public function validate()
    {
        if (!$this->yml)
            return $this->$errors;
        $keys = array_keys($this->yml->getConfiguration());
        foreach ($keys as $key) {
            $setting = $this->yml->get($key);
            if ($setting instanceof BedrockNode) {
                $this->doValidation($setting);
            }
        }
    }
    
    
    protected function doValidation($setting, $parent_class = null)
    {
        foreach ($setting as $name => $result) {
            $value      = $setting->get($name);
            $is_setting = $value instanceof BedrockNode;
            $class      = $result->getBaseClass();
            $validator  = SilverSmithSpec::get($name);
            if (!$validator) {
                $validator = SilverSmithSpec::get($class);
            }
            if (!$validator) {
                $validator = SilverSmithSpec::get($class . ".AvailableNodes.{$name}");
            }
            if (!$validator && $parent_class) {
                $validator = SilverSmithSpec::get($parent_class . ".AvailableNodes.{$name}");
            }
            
            if (!$validator) {
                continue;
            }
            //echo "Using validator {$validator->getPath()} for $name<br />";
            // If the setting came back as an object
            if ($is_setting) {
                // Check to make sure all nodes in the tree are allowed.
                if (!$validator->getUserDefined() && ($allowed_nodes = $validator->getAvailableNodes())) {
                    $allowed = array_keys($allowed_nodes->toArray());
                    foreach ($value as $node => $config) {
                        if (!in_array($node, $allowed)) {
                            $this->errors[] = sprintf(_t('Bedrock.NODENOTALLOWED', 'The node %s is not allowed in %s'), $node, $name);
                        }
                    }
                }
                // If this is a user-defined node (e.g. Fields), make sure there are no reserved nodes floating around.
                elseif ($validator->getUserDefined()) {
                    foreach ($value as $node => $config) {
                        if (class_exists("Bedrock" . $node)) {
                            $this->errors[] = sprintf(_t('Bedrock.NODENOTALLOWED', 'The node %s is note allowed in %s'), $node, $name);
                        }
                    }
                }
                // Make sure any required nodes are present.
                if ($required = $validator->getRequiredNodes()) {
                    if ($is_setting) {
                        foreach ($required as $node) {
                            if (!$value->get($node)) {
                                $this->errors[] = sprintf(_t('Bedrock.NODEISREQUIRED', 'The node %s is required in %s'), $node, $name);
                            }
                        }
                    }
                }
            }
            // If the setting came back as a string.
            else {
                // Make sure it's not supposed to be an object
                if ($type = $validator->getDataType()) {
                    if ($type == "setting") {
                        $this->errors[] = sprintf(_t('Bedrock.MUSTBESETTING', 'The node "%s" should be a collection of nodes, not a plain value.'), $name);
                    } else {
                        $this->validateDataType($value, $type, $name);
                    }
                }
                if ($vals = $validator->getPossibleValues()) {
                    $this->validatePossibleValues($value, $vals, $name);
                }
                
            }
            
            
            if ($is_setting) {
                $this->doValidation($value, $result->getBaseClass());
            }
        }
    }
    
    
    protected function validateDataType($value, $type, $name)
    {
        if ($type == "string" && !is_string($value))
            $this->errors[] = sprintf(_t('Bedrock.VALUEMUSTBESTRING', 'The value of %s must be a string. Right now it is %s'), $name, $value);
        elseif ($type == "integer") {
            $i = (int) $value;
            if (!is_numeric($value) || !is_int($i)) {
                $this->errors[] = sprintf(_t('Bedrock.VALUEMUSTBEINT', 'The value of %s must be an integer. Right now it is %s'), $name, $value);
            }
        } elseif ($type == "boolean" && (!in_array($value, array(
            'true',
            'false'
        )) && !is_bool($value)))
            $this->errors[] = sprintf(_t('Bedrock.VALUEMUSTBEBOOLEAN', 'The value of %s must be a boolean. Right now it is %s.'), $name, $value);
        
    }
    
    
    protected function validatePossibleValues($value, $allowed, $n)
    {
        $valid = false;
        foreach ($allowed as $a) {
            if (preg_match('/^' . $a . '$/', $value)) {
                $valid = true;
                break;
            }
        }
        if (!$valid) {
            $this->errors[] = sprintf(_t('Bedrock.VALUENOTALLOWED', '"%s" is not a valid value for %s. Allowed values are %s'), $value, $n, implode(', ', $allowed));
        }
        
    }
    
}
