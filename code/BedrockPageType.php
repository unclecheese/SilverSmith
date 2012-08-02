<?php

class BedrockPageType extends BedrockDataRecord {
    public function getParent()
    {
        if ($this->getDecorator())
            return "DataExtension";
        if ($this->key == "Page")
            return "SiteTree";
        return $this->get('Parent') ? $this->get('Parent') : "Page";
    }
    
    
    public function getGeneratedCode()
    {
        $path = SilverSmith::get_script_dir() . "/code/lib/structures/PageTypeCode.bedrock";
        if (file_exists($path)) {
            $template = new BedrockTemplate(file_get_contents($path));
            $template->bind($this);                        
            return $template->render();
            
        }
        
    }
    
    
    
    public function write()
    {        
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
    
    
    public function getAllowedChildren()
    {
        if ($children = $this->get('AllowedChildren')) {
            if (!is_array($children)) {
                return array(
                    $children
                );
            }
            return $children;
        }
        return false;
        
    }

    
    
}
