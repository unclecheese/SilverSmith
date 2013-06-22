<?php

/**
 * Defines a DataObject or SiteTree object that is described in the SilverSmith
 * project definition file
 *
 * Bedrock is a PHP library built by Aaron Carlino that turns YAML in to traversable objects.
 * It is sensitive to other classes that contain the prefix "Bedrock"
 * https://github.com/unclecheese/bedrock
 * 
 *	
 * @package SilverSmith
 * @author Aaron Carlino <unclecheese@leftandmain.com>
 */
class BedrockDataRecord extends SilverSmithNode {


	/**
	 * @var string The delimiter that marks the beginning of SilverSmith-generated code
	 *				in the model portion of a SilverStripe PHP file
	 */
    static $model_open = "/** //--// **/";
    


	/**
	 * @var string The delimiter that marks the end of SilverSmith-generated code
	 *				in the model portion of a SilverStripe PHP file
	 */
    static $model_close = "/** --//-- **/";
    
    
    
	/**
	 * @var string The delimiter that marks the beginning of SilverSmith-generated code
	 *				in the controller portion of a SilverStripe PHP file
	 */    
    static $controller_open = "/** /-/ **/";
    


	/**
	 * @var string The delimiter that marks the end of SilverSmith-generated code
	 *				in the controller portion of a SilverStripe PHP file
	 */    
    static $controller_close = "/** -/- **/";
    
    
    
    
    /**
     * Gets an array of $db vars for the generated code, in $FieldName => $FieldType pairs
     *
     * @return BedrockNode
     */
    public function getDBVars() {
        if ($this->getFields()) {
            $db = array();
            foreach ($this->getFields() as $f) {
                if ($f->getDBField()) {
                    $db[$f->key] = $f->getDBField();
                }
            }

            return new BedrockNode("Root",$db, "Root");                        
        }

        return false;
    }
    
    
    
    /**
     * Gets an array of $has_one vars for the generated code, in $RelationName => $ClassName pairs
     *
     * @return BedrockNode
     */    
    public function getHasOneVars() {
        $has_one = array();
        if ($this->getFields()) {
            foreach ($this->getFields() as $f) {
                if ($f->getConfigVar('HasOne')) {
                    $has_one[$f->key] = $f->getConfigVar('HasOne');
                }
            }
        }
        if ($this->getComponents()) {
            foreach ($this->getComponents() as $c) {
                if ($c->getType() == "one") {
                    $has_one[$c->getClass()] = $c->getClass();
                }
            }
        }
        
        return empty($has_one) ? false : new BedrockNode("Root",$has_one,"Root");
    }
    
    
    
    
    /**
     * Gets the definition of a given relation for this object.
     * e.g. $myPageTypeNode->relation('StaffMembers')
     *
     * @param string The name of the relation
     * @return BedrockNode
     */
    public function relation($type) {
        if ($this->getComponents()) {
            $relations = array();
            foreach ($this->getComponents() as $c) {
                if ($c->getType() == $type) {
                    $relations[$c->getName()] = $c->getClass();
                }
            }
            return empty($relations) ? false : new BedrockNode("Root",$relations, "Root");
        }
        
        return false;
    }
    
    
    
    /**
     * Gets an array of $has_many vars for the generated code, in $RelationName => $ClassName pairs
     *
     * @return BedrockNode
     */        
    public function getHasManyVars() {
        return $this->relation("many");
    }
    
    
    
    /**
     * Gets an array of $many_many vars for the generated code, in $RelationName => $ClassName pairs
     *
     * @return BedrockNode
     */        
    public function getManyManyVars() {
        return $this->relation("manymany");
    }
    
    
    
    /**
     * Gets an array of $belongs_many_many vars for the generated code, in $RelationName => $ClassName pairs
     *
     * @return BedrockNode
     */        
    public function getBelongsManyManyVars() {
        $belongs = array();
        foreach (SilverSmithProject::get_all_nodes() as $node) {
            if ($components = $node->getComponents()) {
                if ($me = $components->get($this->key)) {
                    if ($me->getType() == "manymany") {
                        $belongs[SilverSmithUtil::pluralize($node->getKey())] = $node->getKey();
                    }
                }
            }
        }
        
        return empty($belongs) ? false : new BedrockNode("Root",$belongs,"Root");
    }
    
    
    
    /**
     * Determines if this class will be defined as a decorator, e.g. {@link DataExtension} subclass
     *
     * @return bool
     */
    public function getDecorator() {    
        return class_exists($this->getKey()) && $this->recursive_file_exists($this->key.'.php' , SilverSmith::get_project_dir().'/code/') === false;
    }
    
    
    
    
    /**
     * Binds this object to a {@link BedrockTemplate} which outputs all of the model vars
     * e.g. $db, $has_one, $has_many, $many_many, $belongs_many_many	
     *
     * @return BedrockNode
     */
    public function getModelVars() {        
        $path = SilverSmith::get_script_dir() . "/code/lib/structures/ModelVars.bedrock";
        if (file_exists($path)) {
            $template = new BedrockTemplate(file_get_contents($path));
            $template->bind($this);            
            return $template->render();
        }
        
    }
    
    

    /**
     * Determines if this record is associated with a SiteTree object
     *
     * @return bool
     */
    public function getIsPage() {
        $parts = explode('.', $this->path);
        
        return ($parts[sizeof($parts) - 2] == "PageTypes");
    }
    
    
    
    
    /**
     * Determines if this is a "standalone" component that is not defined under
     * a page or parent component. i.e. the type of component that is managed in
     * ModelAdmin
     *
     * @return bool
     */
    public function getIsModelAdmin() {
        $parts = explode('.', $this->path);
        
        return reset($parts) == "Components";
    }
    
    
    
    /**
     * Gets the path to the PHP file that contains the class for this object
     *
     * @return string
     */
    public function getFilePath() {
        if ($this->getDecorator()) {
            //search if Decorator exists already
            $subdir = $this->recursive_file_exists("{$this->key}Decorator.php", SilverSmith::get_project_dir().'/code/');
            return SilverSmith::get_project_dir()."/code/{$subdir}{$this->key}Decorator.php";
           
        }

        $subdir = $this->recursive_file_exists($this->key.'.php', SilverSmith::get_project_dir().'/code/');        
        return SilverSmith::get_project_dir()."/code/{$subdir}{$this->key}.php";
    }
    
    
    
    /**
     * Determines if the file for this object has been created yet
     *
     * @return bool
     */
    public function isNew() {
        return !file_exists($this->getFilePath());
    }
    
    
    
    
    /**
     * Returns the content type for this object, e.g. "PageType" or "Component"
     *
     * @return string
     */
    public function getContentType() {
        return str_replace("Bedrock", "", get_class($this));
    }
    
    
    
    
    /**
     * Gets the code to define the getCMSFields() function for this object
     *
     * @return string
     */
    public function getGetCMSFieldsCode() {
        $template = new BedrockTemplate(file_get_contents(SilverSmith::get_script_dir() . "/code/lib/structures/getCMSFields.bedrock"));
        $template->bind($this);        
        return $template->render();
    }
    
    
    
    
    /**
     * This is the function that does most of the legwork for creating the PHP code for 
     * this node. Note teh replacement of [?php tags to allow the eval() function 
     * in {@link BedrockTemplate} to work properly
     *
     * @return array A list of the differences in the file
     */
    public function updateFile() {
        $path             = $this->getFilePath();
        $content          = file_get_contents($path);
        $original_content = $content;
        $content          = str_replace("<?php", "[?php", $content);
        $content          = SilverSmithUtil::replace_tags(BedrockDataRecord::$model_open, BedrockDataRecord::$model_close, "\n\n<@= GeneratedCode @>\n\n\t", $content);
        $template         = new BedrockTemplate($content);
        $template->bind($this);        
        $new_content = str_replace("[?php", "<?php", $template->render());
            
        // Ensure getCMSFields		
        if ($this->getIsFinal()) {
            $func = $this->getDecorator() ? "updateCMSFields" : "getCMSFields";
            
            if (!preg_match('/function[\s]+' . $func . '\(/', $new_content)) {
                $new_content = str_replace(self::$model_close, self::$model_close . "\n\n" . $this->getGetCMSFieldsCode(), $new_content);
            }
        }
        // Ensure parent
        $pattern = "/([A-Za-z0-9_]+)[\s]+extends[\s]+([A-Za-z0-9_]+)/";
        preg_match_all($pattern, $new_content, $matches, PREG_SET_ORDER);
        if ($matches) {
            foreach ($matches as $set) {
                if (isset($set[2])) {
                    if ((substr($set[1], -11) == "_Controller") && ($set[2] != $this->getParent() . "_Controller")) {
                        //say("Replacing ".$set[1] . " extends " . $set[2] . " with " . $set[1] . " extends {$this->getParent()}_Controller");					
                        $new_content = preg_replace('/' . $set[1] . '[\s]+extends[\s]+([A-Za-z0-9_]+)/', $set[1] . " extends {$this->getParent()}_Controller", $new_content);
                    } elseif ((substr($set[1], -11) != "_Controller") && ($set[2] != $this->getParent())) {
                        //	say("Replacing ".$set[1] . " extends " . $set[2] . " with " . $set[1] . " extends {$this->getParent()}");
                        $new_content = preg_replace('/' . $set[1] . '[\s]+extends[\s]+([A-Za-z0-9_]+)/', $set[1] . " extends {$this->getParent()}", $new_content);
                    }
                }
            }
            
        }
        
        $fh = fopen($path, "w");
        fwrite($fh, $new_content);
        fclose($fh);

        return SilverSmithUtil::get_text_diff($original_content, $new_content);
    }
    
    
    
    
    /**
     * Creates a PHP file on the disk for the class that will define this node
     *
     */
    public function createFile() {
        $path     = $this->getFilePath();
        $stock    = $this->getDecorator() ? "Decorator" : $this->getContentType();
        $content  = file_get_contents(SilverSmith::get_script_dir() . "/code/lib/structures/$stock.bedrock");
        $template = new BedrockTemplate($content);
        $template->bind($this);        
        $new_content = str_replace("[?php", "<?php", $template->render());
        $fh          = fopen($path, "w");
        fwrite($fh, $new_content);
        fclose($fh);
    }
    
    
    
    
    /**
     * Determines if this node is still under the control of SilverSmith, e.g. 
     * if the delimiters have been removed
     *
     * @return bool
     */
    public function isSilverSmithed() {
        if ($contents = @file_get_contents($this->getFilePath())) {
            return (stristr($contents, self::$model_open) !== false);
        }
    }
    
    
    
    
    /**
     * Get the fields that are hidden in the getCMSFields() function
     *
     * @return array
     */
    public function getHide() {
        if ($hides = $this->get('Hide')) {
            if (is_string($hides)) {
                return SilverSmithNode::create("Root",array(
                    $hides
                ), null, array());
            }
            return $hides;
        }
        
        return false;
    }
    
    
    
    
    /**
     * Gets the children the class and checks if they are under the management of SilverSmith
     *
     * @return bool
     */
    public function getHasSilverSmithedChildren() {
        foreach (SilverSmithProject::get('PageTypes') as $p) {
            if (($p->getParent() == $this->key) && $p->isSilverSmithed()) {
                return true;
            }
        }
    }
    
    
    
    /**
     * Determines if the class that this node represents has any parents that are managed by SilverSmith
     *
     * @return bool
     */
    public function getHasSilverSmithedParents() {
        if ($node = SilverSmithProject::get_node($this->getParent())) {
            return $node->isSilverSmithed();
        }
        
        return false;
    }
    
    
    
    
    /**
     * A negation method to {@link getHasSilverSmithedParents()}
     *
     * @return bool
     */
    public function getHasNoSilverSmithedParents() {
        return !$this->getHasSilverSmithedParents();
    }
    
    


    /**
     * A negation method to {@link getHasSilverSmithedChildren()}
     *
     * @return bool
     */
    public function getHasNoSilverSmithedChildren() {
        return !$this->getHasSilverSmithedChildren();
    }
    
    
    
    
    /**
     * Determines if the class that this node represents is at the bottom of the inheritance chain.
     * This is important to prevent the getGeneratedCMSFields() function, which calls getCMSFields(),
     * from getting stuck in an infinite loop.
     *
     * @return bool
     */    
    public function getIsFinal() {
        return !$this->getHasSilverSmithedChildren();
    }
    
    /*
     * Search recursively for a file in a given directory
     *
     * @param string $filename The file to find
     *
     * @param string $directory The directory to search
     *
     * @return string subdirectory where file exists
     * @see http://www.phpro.org/examples/Recursive-File-Exists.html
     */
    function recursive_file_exists($filename, $directory)
    {
        try
        {
            //check if it exists in $directory
            if (file_exists($directory.$filename)) {
                return '';
            }
            
            //check all subdirectories            
            foreach(new recursiveIteratorIterator($currentdir = new recursiveDirectoryIterator($directory)) as $file)
            {
                if( $directory.$currentdir.'/'.$filename == $file )
                {
                    return $currentdir . '/';
                }
            }
            return false;
        }
        catch(Exception $e)
        {
             return false;
        }
    }     
    
    
}
