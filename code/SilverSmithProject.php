<?php

class SilverSmithProject
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
    
    
    public static function get_configuration()
    {
        return self::$settings_list;
    }
    
    
    
    public static function get_page_types()
    {
        $ret = array();
        if ($pages = self::get('PageTypes')) {
            foreach ($pages as $page) {
                $ret[] = $page;
            }
        }
        return $ret;
    }
    
    
    public static function get_components()
    {
        $ret = array();
        if ($pages = self::get_page_types()) {
            foreach ($pages as $page) {
                if ($components = $page->getComponents()) {
                    foreach ($components as $component) {
                        if ($component->getFields() || $component->getDecorator()) {
                            $ret[$component->getKey()] = $component;
                        }
                        if ($subcomponents = $component->getComponents()) {
                            foreach ($subcomponents as $sub) {
                                if ($sub->getFields() || $sub->getDecorator()) {
                                    $ret[$sub->getKey()] = $sub;
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($components = self::get('Components')) {
            foreach ($components as $component) {
                if ($component->getFields() || $component->getDecorator()) {
                    $ret[$component->getKey()] = $component;
                }
                if ($subcomponents = $component->getComponents()) {
                    foreach ($subcomponents as $sub) {
                        if ($sub->getFields() || $sub->getDecorator()) {
                            $ret[$sub->getKey()] = $sub;
                        }
                    }
                }
            }
        }
        return $ret;
    }
    
    
    public static function get_node($name)
    {
        foreach (self::get_all_nodes() as $node) {
            if ($node->getKey() == $name)
                return $node;
        }
        return false;
    }
    
    
    public static function get_all_nodes()
    {
        return array_merge(self::get_page_types(), self::get_components());
    }
}
