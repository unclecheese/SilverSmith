<?php



/**
 * Defines a child node of the PageTypes node in a project defintion YAML file
 *
 * Bedrock is a PHP library built by Aaron Carlino that turns YAML in to traversable objects.
 * It is sensitive to other classes that contain the prefix "Bedrock"
 * https://github.com/unclecheese/bedrock
 * 	
 * @package SilverSmith
 * @author Aaron Carlino <unclecheese@leftandmain.com>
 */
class BedrockPageType extends BedrockDataRecord {



	/**
	 * Gets the parent class for this page type, e.g. "Page"
	 *
	 * @return string
	 */
    public function getParent() {
        if ($this->getDecorator())
            return "DataExtension";
        if ($this->key == "Page")
            return "SiteTree";
        return $this->get('Parent') ? $this->get('Parent') : "Page";
    }
    
    
    
    /**
     * Binds the component to a {@link BedrockTemplate} and creates the PHP code that defines the
     * component in SilverStripe
     *
     * @return string
     */
    public function getGeneratedCode()
    {
        $path = SilverSmith::get_script_dir() . "/code/lib/structures/PageTypeCode.bedrock";
        if (file_exists($path)) {
            $template = new BedrockTemplate(file_get_contents($path));
            $template->bind($this);                                    
            return $template->render();
            
        }
        
    }
    
    
    
    /**
     * Writes the generated code to a .php file
     *
     * @return void
     */
    public function write() {        
        if (!file_exists("code/{$this->key}.php")) {
            $fh   = fopen("code/{$this->key}.php", "w");
            $path = SilverSmith::get_script_dir() . "/code/lib/structures/PageType.bedrock";
            if (file_exists($path)) {
                $template = new BedrockTemplate($path);
                $template->bind($this);                
                fwrite($fh, $template->render());
                fclose($fh);
            }
            
        }
    }
    
    
    
    
    /**
     * A wrapper method for the AllowedChildren node. Normalizes the output into
     * an array, in case the value is scalar, e.g. AllowedChildren: BlogPage
     *
     * @return array
     */
    public function getAllowedChildren()
    {
        if ($children = $this->get('AllowedChildren')) {
            if ($children instanceof BedrockNode) {
                return $children;
            }
            return new SilverSmithNode("Root", array($children), "Root.AllowedChildren", array());
        }
        return false;
        
    }

    
    
}
