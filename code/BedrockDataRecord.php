<?php

class BedrockDataRecord extends SilverSmithNode {
    static $model_open = "/** //--// **/";
    
    static $model_close = "/** --//-- **/";
    
    static $controller_open = "/** /-/ **/";
    
    static $controller_close = "/** -/- **/";
    
    
    
    public function getDBVars()
    {
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
    
    
    public function getHasOneVars()
    {
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
    
    public function relation($type)
    {
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
    
    
    public function getHasManyVars()
    {
        return $this->relation("many");
    }
    
    public function getManyManyVars()
    {
        return $this->relation("manymany");
    }
    
    public function getBelongsManyManyVars()
    {
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
    
    
    public function getDecorator()
    {
        return class_exists($this->getKey()) && !file_exists(SilverSmith::get_project_dir()."/code/{$this->key}.php");
    }
    
    
    
    public function getModelVars()
    {        
        $file = $this->getDecorator() ? "ModelVarsDecorator" : "ModelVars";
        $path = SilverSmith::get_script_dir() . "/code/lib/structures/{$file}.bedrock";
        if (file_exists($path)) {
            $template = new BedrockTemplate(file_get_contents($path));
            $template->bind($this);
            return $template->render();
        }
        
    }
    
    
    public function getIsPage()
    {
        $parts = explode('.', $this->path);
        return ($parts[sizeof($parts) - 2] == "PageTypes");
    }
    
    
    public function getIsModelAdmin()
    {
        $parts = explode('.', $this->path);
        return reset($parts) == "Components";
    }
    
    
    public function getFilePath()
    {
        if ($this->getDecorator()) {
            return SilverSmith::get_project_dir()."/code/{$this->key}Decorator.php";
        }
        return SilverSmith::get_project_dir()."/code/{$this->key}.php";
    }
    
    
    public function isNew()
    {
        return !file_exists($this->getFilePath());
    }
    
    
    public function getContentType()
    {
        return str_replace("Bedrock", "", get_class($this));
    }
    
    
    public function getGetCMSFieldsCode()
    {
        $template = new BedrockTemplate(file_get_contents(SilverSmith::get_script_dir() . "/code/lib/structures/getCMSFields.bedrock"));
        $template->bind($this);
        return $template->render();
    }
    
    
    public function updateFile()
    {
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
    
    
    public function createFile()
    {
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
    
    
    public function isSilverSmithed()
    {
        if ($contents = @file_get_contents($this->getFilePath())) {
            return (stristr($contents, self::$model_open) !== false);
        }
    }
    
    
    public function getHide()
    {
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
    
    
    public function getHasSilverSmithedChildren()
    {
        foreach (SilverSmithProject::get('PageTypes') as $p) {
            if (($p->getParent() == $this->key) && $p->isSilverSmithed()) {
                return true;
            }
        }
    }
    
    
    public function getHasSilverSmithedParents()
    {
        if ($node = SilverSmithProject::get_node($this->getParent())) {
            return $node->isSilverSmithed();
        }
        return false;
    }
    
    
    public function getHasNoSilverSmithedParents()
    {
        return !$this->getHasSilverSmithedParents();
    }
    
    
    public function getHasNoSilverSmithedChildren()
    {
        return !$this->getHasSilverSmithedChildren();
    }
    
    
    public function getIsFinal()
    {
        return !$this->getHasSilverSmithedChildren();
    }
    
    
    
    
    
}
