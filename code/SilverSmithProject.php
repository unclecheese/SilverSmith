<?php



/**
 * A static class that offers utility methods for traversing and searching the
 * SilverSmith project configuration file
 *
 * @package SilverSmith
 * @author Aaron Carlino <unclecheese@leftandmain.com>
 */
class SilverSmithProject
{


	/**
	 * @var BedrockNode 
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
     * Get a setting from the project YAML
     *
     * @param string A dot-separated path to the setting
     */    
    public static function get($setting) {
        return self::$settings_list->get($setting);
    }
    
    
    
    
    /**
     * Gets the BedrockYAML object representing the SilverSmith project configuration file
     *
     * @return BedrockYAML
     */
    public static function get_configuration() {
        return self::$settings_list;
    }
    
    
    
    
    /**
     * Gets all PageType nodes in the project
     *
     * @return array
     */
    public static function get_page_types() {
        $ret = array();
        if ($pages = self::get('PageTypes')) {
            foreach ($pages as $page) {
                $ret[] = $page;
            }
        }
        return $ret;
    }
    
    
    
    
    /**
     * Gets all Component nodes in the project, standalone or paired with a Page
     *
     * @return array
     */
    public static function get_components() {
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
                            if(isset($ret[$sub->getKey()])) {
                                if(!$ret[$sub->getKey()]->getFields()) {
                                    $ret[$sub->getKey()] = $sub;        
                                }
                            }                            
                        }
                    }
                }
            }
        }
        return $ret;
    }
    
    
    
    
    /**
     * Gets a node by name, whether a PageType or Component
     *
     * @param string The name of the node
     * @return SilverSmithNode
     */
    public static function get_node($name) {
        foreach (self::get_all_nodes() as $node) {
            if ($node->getKey() == $name)
                return $node;
        }
        return false;
    }
    
    
    
    
    /**
     * Gets all the defined nodes in the project. If a node is defined twice, use the one where Fields
     * are defined
     *
     * @return array
     */
    public static function get_all_nodes() {
        $merged = array_merge(self::get_page_types(), self::get_components());
        $used = array ();
        foreach($merged as $node) {
            if(isset($used[$node->getKey()])) {                
                $existing = $used[$node->getKey()];
                if($existing->getFields() && !$node->getFields()) {
                    continue;
                }
                if($existing->getComponents() && !$node->getComponents()) {
                    continue;
                }                
            }
            $used[$node->getKey()] = $node;            
        }
        return array_values($used);
    }
}
