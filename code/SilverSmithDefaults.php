<?php

class SilverSmithDefaults
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
    
    
}
