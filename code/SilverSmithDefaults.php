<?php



/**
 * A wrapper class for a {@link BedrockYAML} instance. Stores all of the default, or fallback, values
 * for various settings in SilverSmith
 *
 * @todo It should be possible to override this and merge in custom settings	
 * @package SilverSmith
 * @author Aaron Carlino <unclecheese@leftandmain.com>
 */
class SilverSmithDefaults
{


	/**
	 * @var BedrockYAML The BedrockYAML object represeting the default settings
	 */
    protected static $settings_list;
    
    
    
    
    /**
     * Load the default settings from a path to a YAML file 
     *
     * @param string the path to the YAML
     */
    public static function load($path) {
        self::$settings_list = new BedrockYAML($path);
    }
    
    
    
    
    /**
     * Get a setting from the default YAML
     *
     * @param string A dot-separated path to the setting
     */
    public static function get($setting) {
        return self::$settings_list->get($setting);
    }
    
    
}
