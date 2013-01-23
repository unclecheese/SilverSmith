<?php



/**
 * A wrapper class for accessing the specifications for the SilverSmith project configuration file.
 * Used primarily for validating the project yaml.
 *
 * @package SilverSmith
 * @author Aaron Carlino <unclecheese@leftandmain.com>
 */
class SilverSmithSpec {



	/**
	 * @var BedrockYAML A cached object representing the spec.yml 
	 */
    protected static $settings_list;
    
    
    
    /**
     * Loads the YAML into a {@link BedrockYAML} object given a file path
     *
     * @param string the path to the YAML file
     */
    public static function load($path) {
        self::$settings_list = new BedrockYAML($path);
    }
    
    
    
    /**
     * Gets a given setting from the spec
     *
     * @param string The dot-separated path to the setting
     * @return BedrockNode
     */
    public static function get($setting) {
        return self::$settings_list->get($setting);
    }
    
    
    
    
    /**
     * Gets a translatable list of a given setting
     * Future-proofing for a GUI
     *
     * @param string The dot-separated path to the setting
     * @return 
     */
    public static function get_i18n_map($path) {
        $map = array();
        if ($result = self::get($path)) {
            foreach ($result as $name => $setting) {
                $map[$name] = _t('ProjectBuilder.' . strtoupper($name), $setting->getLabel());
            }
        }
        return $map;
    }
    
    
    
    
    /**
     * Future-proofing for a GUI. Provide translatable entities for the spec
     *
     * @return array
     */
    public function provideI18nEntities() {
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




/**
 * A class that validates a given project configuration file based on the spec.yml	
 *
 * @package SilverSmith
 * @author Aaron Carlino <unclecheese@leftandmain.com>
 */
class SilverSmithSpec_Validator {



	/**
	 * @var array A list of errors in the YAML
	 */
    protected $errors = array();
    
    
    /**
     * @var BedrockYAML The object representing the YAML input to be validated	
     */
    protected $yml;
    
    
    
    /**
     * Create a new instances of the validator
     *
     * @param string The path to the YAML file to validate
     */
    public function __construct($path) {
        $this->yml = new BedrockYAML($path);
        $this->validate();
    }
    
    
    
    
    /**
     * Get all of the errors in the project YAML file
     *
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }
    
    
    
    
    /**
     * Validate the YAML file based on the spec.yml
     *
     */
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
    
    
    
    
    /**
     * A recursive function that validates the project YAML based on the spec.yml
     *
     * @param BedrockNode The node to validate
     * @param string The name of the parent node	
     */
    protected function doValidation($setting, $parent_class = null) {
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
    
    
    
    
    /**
     * Validates a node to make sure it is the right data type
     *
     * @param mixed The value to validate
     * @param string The type of data that the value is required to be
     * @param string The name of the node being validated
     */
    protected function validateDataType($value, $type, $name) {
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
    
    
    
    
    
    /**
     * Validates a node to make sure its value is within the list of allowed values
     *
     * @param mixed The value of the node
     * @param array The list of allowed values
     * @param string The name of the node
     */
    protected function validatePossibleValues($value, $allowed, $n) {
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
