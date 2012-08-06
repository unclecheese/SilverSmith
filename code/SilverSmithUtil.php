<?php


class SilverSmithUtil
{
    /**
     * If only a single tab name is given, make it easy and apply it to "Root.Content"
     *
     * @param string $tab The name of the tab
     * @return string
     */
    public static function clean_tab($tab)
    {
        if (false === stristr($tab, "Root."))
            $tab = "Root.Content.{$tab}";
        return $tab;
    }
    
    
    public static function generate_i18n_namespace()
    {
        if ($namespace = SilverSmithDefaults::get('DefaultNamespace')) {
            return $namespace;
        }
        return ucwords(project());
    }
    
    
    public static function generate_i18n_entity($text)
    {
        $words     = explode(' ', $text);
        $max_words = SilverSmithDefaults::get('EntityWordCount');
        if (count($words) > $max_words) {
            $words = array_slice($words, 0, $max_words);
        }
        $str = implode('', $words);
        return strtoupper(singleton('SiteTree')->generateURLSegment($str));
    }
    
    
    public static function generate_unique_i18n_entity($text, $namespace)
    {
        $original_entity = self::generate_i18n_entity($text);
        $entity          = $original_entity;
        $i               = 1;
        while (self::i18n_entry_exists($namespace, $entity)) {
            $entity = $original_entity . $i;
            $i++;
        }
        return $entity;
    }
    
    
    
    public static function pluralize($str)
    {
        $name = $str;
        return substr($name, -1, 1) == "y" ? substr_replace($name, "ies", -1, 1) : $name . "s";
    }
    
    
    
    public static function singularize($str)
    {
        $name = SilverSmithUtil::proper_form($str);
        if (substr($name, -1, 3) == "ies") {
            return substr_replace($name, "y", -1, 3);
        } else if (substr($name, -1, 1) == "s") {
            return substr_replace($name, "", -1, 3);
        }
        return $str;
    }
    
    
    public static function replace_tags($startPoint, $endPoint, $newText, $source)
    {
        return preg_replace('#(' . preg_quote($startPoint) . ')(.*)(' . preg_quote($endPoint) . ')#si', '$1' . $newText . '$3', $source);
    }
    
    
    
    
    public static function proper_form($str)
    {
        return preg_replace('/[^A-Za-z0-9_]/', "", $str);
    }
    
    
    public static function tabify($str)
    {
        return str_replace("{T}", "\t", $str);
    }
    
    
    
    public static function get_lipsum($length = 1, $rich = false)
    {
        $tags = $rich ? "link" : "";
        $text = @file_get_contents("http://loripsum.net/api/{$length}/short/{$tags}");
        if (!$rich) {
            $text = strip_tags($text);
        }
        return ($text && !empty($text)) ? $text : SilverSmithDefaults::get('DefaultContent');
    }
    
    public static function get_lipsum_words($num = 5)
    {
        $str   = self::get_lipsum();
        $words = explode(' ', $str);
        return implode(' ', array_slice($words, 8, $num));
    }
    
    public static function get_default_content(DBField $fieldType)
    {
        switch (get_class($fieldType)) {
            case "Boolean":
                return rand(0, 1);
                break;
            
            case "Currency":
            case "Money":
                return rand(0, 1000) . ".00";
                break;
            
            case "Date":
            case "Datetime":
            case "Time":
                $one_year_ago    = strtotime("-1 year");
                $one_year_future = strtotime("+1 year");
                return date('Y-m-d H:i:s', rand($one_year_ago, $one_year_future));
                break;
            
            case "Decimal":
                return rand(0, 100) . "." . rand(10, 99);
                break;
            
            case "Double":
            case "Float":
                return "0." . rand(0, 100);
                break;
            
            case "Enum":
            case "MultiEnum":
                $map  = $fieldType->enumValues();
                $keys = array_keys($map);
                $max  = sizeof($keys) - 1;
                return $keys[rand(0, $max)];
                break;
            
            case "ForeignKey":
                return 0;
                break;
            
            case "HTMLText":
                return self::get_lipsum(5, true);
                break;
            
            case "HTMLVarchar":
                return self::get_lipsum(1, true);
                break;
            
            case "Int":
                return rand(0, 1000);
                break;
            
            case "Percentage":
                return "0." . rand(0, 99);
                break;
            
            case "PrimaryKey":
                return 0;
                break;
            
            case "Text":
                return self::get_lipsum();
                break;
            
            case "StringField":
            case "Varchar":
                return self::get_lipsum_words(rand(2, 8));
                break;
            
            case "Year":
                return rand((date('Y') - 10), (date('Y') + 10));
                break;
            
            default:
                return 0;
                break;
                
        }
        
        
    }
    
    
    public static function get_text_diff($old_content, $new_content)
    {
        $old_file = explode("\n", $old_content);
        $new_file = explode("\n", $new_content);
        $diff     = new Text_Diff('auto', array(
            $old_file,
            $new_file
        ));
        $added    = $diff->countAddedLines();
        $deleted  = $diff->countDeletedLines();
        if ($added == 0 && $deleted == 0) {
            return false;
        }
        $net            = ($added - $deleted);
        $result         = array();
        $result_added   = 0;
        $result_changed = 0;
        $result_deleted = 0;
        if ($net == 0) {
            $result_changed = $added;
        } elseif ($net > 0) {
            $result_changed = ($added - $net);
            $result_added   = $net;
        } elseif ($net < 0) {
            $result_changed = ($deleted - abs($net));
            $result_deleted = abs($net);
        }
        
        return array(
            'added' => $result_added,
            'deleted' => $result_deleted,
            'changed' => $result_changed
        );
    }
    
    public static function add_default_content(&$object, $level, $onlyFields = array ())
    {    
        if ($level < 2)
            return;
        $fields = array ();
        if(!empty($onlyFields)) {
            foreach($onlyFields as $f) {
                $fields[] = trim($f);
            }
        }
        $site_tree   = singleton('SiteTree');
        $data_object = singleton('DataObject');
        $is_sitetree = ($object->class == "SiteTree" || is_subclass_of($object, "SiteTree"));
        foreach ($object->db() as $field => $type) {
            if ($data_object->db($field) || ($is_sitetree && $site_tree->db($field)) || (!empty($fields) && !in_array($field, $fields))) {
                continue;
            }
            if (!$object->$field) {
                if(strstr($field, "Email")) {
                    $object->$field = preg_replace('/[^a-z@\.]/','',strtolower(self::get_lipsum_words(1)."@".self::get_lipsum_words(1).".com"));
                }
                elseif(strstr($field, "Phone")) {
                    $object->$field = rand(100,999)."-".rand(100,999)."-".rand(100,999);
                }
                else {
                    $object->$field = self::get_default_content($object->obj($field));
                }
            }
        }
        
        foreach ($object->has_one() as $relation => $class) {
            if ($data_object->has_one($relation) || $site_tree->has_one($relation)|| (!empty($fields) && !in_array($relation, $fields))) {
                continue;
            }
            $filter = ($class == "File") ? "ClassName = 'File'" : null;
            $o = DataList::create($class)->where($filter)->sort("RAND()")->first();            
            if ($o) {
                $key          = $relation . "ID";
                $object->$key = $o->ID;
            }
            
        }
        if ($level > 2) {
            foreach ($object->has_many() as $relation => $class) {
                if ($data_object->has_many($relation) || $site_tree->has_many($relation) || !SilverSmithProject::get_node($class)|| (!empty($fields) && !in_array($relation, $fields))) {
                    continue;
                }
                
                if ($name = array_search($object->class, singleton($class)->has_one())) {
                    $key   = $name . "ID";
                    $count = rand(1, 5);
                    for ($i = 0; $i <= $count; $i++) {
                        if ($candidate = DataList::create($class)->where("$key = 0 OR $key IS NULL")->first()) {
                            $candidate->$key = $object->ID;
                            $candidate->write();
                        } elseif (!is_subclass_of($class, "SiteTree") && !is_subclass_of($class, "File")) {
                            $related       = new $class();
                            $related->$key = $object->ID;
                            $related->write();
                            self::add_default_content($related, $level);
                            $related->write();
                        } else {
                            // We don't create a site tree object for the has many, because it will mess up the hierarchy. 
                        }
                    }
                }
            }            
            foreach ((array) $object->stat('many_many') as $relation => $class) {
                if($class == $object->class || is_subclass_of($class, $object->class)) {continue;}
                if ($data_object->many_many($relation) || $site_tree->many_many($relation) || !SilverSmithProject::get_node($class) || (!empty($fields) && !in_array($relation, $fields))) {
                    continue;
                }
                
                $table     = $object->class . "_" . $relation;
                $parentKey = $object->class . "ID";
                $childKey  = $class . "ID";
                $set       = DataList::create($class)->sort("RAND()")->limit(5);                
                if (!$set) {
                    $set = new DataList();
                }
                
                // never create sitetree or file objects.
                if (!is_subclass_of($class, "SiteTree") && !is_subclass_of($class, "File") && $class != $object->class) {                    
                    $count = $set->Count();
                    while ($count < 5) {
                        $related = new $class();
                        $related->write();
                        self::add_default_content($related, $level);
                        $related->write();
                        $count++;
                    }
                }
                
                $set = DataList::create($class)->sort("RAND()")->limit(rand(1, 5));
                if ($set) {
                    $object->$relation()->setByIDList($set->column('ID'));
                }
             
            }
        }
    }
    
    
    
    public static function i18n_entry_exists($namespace, $entity)
    {
        global $lang;
        $loc = i18n::get_locale();
        if (!isset($lang[$loc]))
            i18n::include_by_locale($loc);
        $arr = $lang[$loc];
        if (isset($arr[$namespace]) && is_array($arr[$namespace])) {
            return isset($arr[$namespace][$entity]);
        }
        return false;
        
    }
    
    
    public static function remove_file_extension($strName)
    {
        $ext = strrchr($strName, '.');
        if ($ext !== false) {
            $strName = substr($strName, 0, -strlen($ext));
        }
        return $strName;
    }
    
    
    public static function clean_array_value($key, $array)
    {
        return isset($array[$key]) ? $array[$key] : "";
    }
    
    
    public static function array_to_yml($array)
    {
        return sfYML::dump($array, 99);
    }
    
    
    public static function remove_empty_values($input)
    {
        // If it is an element, then just return it
        if (!is_array($input)) {
            return $input;
        }
        $non_empty_items = array();
        
        foreach ($input as $key => $value) {
            // Ignore empty cells
            if ($value) {
                // Use recursion to evaluate cells 
                $non_empty_items[$key] = self::remove_empty_values($value);
            }
        }
        
        // Finally return the array without empty items
        return $non_empty_items;
        
    }
    
    public static function to_underscore($str)
    {
        $str[0] = strtolower($str[0]);
        $func   = create_function('$c', 'return "_" . strtolower($c[1]);');
        return preg_replace_callback('/([A-Z])/', $func, $str);
    }


    // http://www.php.net/manual/en/function.getopt.php#83414
    public static function parse_parameters($noopt = array()) {
        $result = array();
        $params = $GLOBALS['argv'];
        // could use getopt() here (since PHP 5.3.0), but it doesn't work relyingly
        reset($params);
        while (list($tmp, $p) = each($params)) {
            if ($p{0} == '-') {
                $pname = substr($p, 1);
                $value = true;
                if ($pname{0} == '-') {
                    // long-opt (--<param>)
                    $pname = substr($pname, 1);
                    if (strpos($p, '=') !== false) {
                        // value specified inline (--<param>=<value>)
                        list($pname, $value) = explode('=', substr($p, 2), 2);
                    }
                }
                // check if next parameter is a descriptor or a value
                $nextparm = current($params);
                if (!in_array($pname, $noopt) && $value === true && $nextparm !== false && $nextparm{0} != '-') list($tmp, $value) = each($params);
                $result[$pname] = $value;
            } else {
                // param doesn't belong to any option
                $result[] = $p;
            }
        }
        return $result;
    }
    
    
    
}
