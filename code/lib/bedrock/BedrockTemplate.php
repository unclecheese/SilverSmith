<?php

/**
 * Accepts text file using template syntax and binds to an object
 * to render its contents
 *
 * @example
 * <code>
 * $yml = new BedrockYML("/path/to/example.yml");
 * $template = new BedrockTemplate("/path/to/template.bedrock");
 * $template->bind($yml);
 * echo $template->render();
 * </code>
 *
 * <code>
 * My template
 * <@ with SomeYAMLNode @>
 *  <@ each :Children @>
 *    This is the <@= :Name @> node.
 *    <@ if SomeProperty @>
 *      SomeProperty is true
 *    <@ else @>
 *      SomeProperty is false.
 *    <@ /if @>
 *  <@ /each @>
 * <@ /with @>
 * </code>
 *
 * @author Aaron Carlino <unclecheese@leftandmain.com>
 * @package Bedrock
 */

class BedrockTemplate {

  
  /**
   * @var string The contents of the raw template to be parsed
   */
  protected $template;

  

  /**
   * @var object The current context of the data for the template
   */
  protected $context;



  /**
   * Initializes the template
   *
   * @param string $contents A template
   */
  public function __construct($contents) {
    $this->template = $contents;
  }



  /**
   * Binds the template to an object to source its data
   *
   * @param object $obj The object that will be the source of the template data
   */
  public function bind($obj) {
    $this->context = $obj;
  }



  /**
   * Parses the raw template and replaces it with PHP code that can
   * be eval'ed
   *
   * @return string the PHP-ready template
   */
  public function generateTemplate() {
    $template = "?>".$this->template;
    $template = str_replace("{T}", "\t", $template);
    $template = preg_replace('/<@ ?else ?@>/','<?php else: ?>', $template);
    $template = preg_replace('/<@ ?\/if ?@>/','<?php endif ?>', $template);
    $template = preg_replace('/<@= :Name @>/', '<?php echo $context->getKey() ?>', $template);
    $template = preg_replace('/<@= :Val @>/', '<?php echo $context ?>', $template);
    $template = preg_replace('/<@= :Pos @>/','<?php echo $pos ?>', $template);
    $template = preg_replace('/\<@= ([A-Za-z0-9_]+)\.([A-Za-z0-9]+) @>/','<?php echo $context->get$1()->get$2() ?>', $template);
    $template = preg_replace('/<@= ([A-Za-z0-9_]+) @>/','<?php echo $context->get$1() ?>', $template);
    $template = preg_replace('/<@ ?(if|elseif) :First ?@>/','<?php $1($first): ?>', $template);
    $template = preg_replace('/<@ ?(if|elseif) :Last ?@>/','<?php $1($last): ?>', $template);
    $template = preg_replace('/<@ ?(if|elseif) ([A-Za-z0-9_]+) (=|!)= [\'\"]([A-Za-z0-9_.]+)[\'\"] ?@>/','<?php $1($context->get$2() $3= "$4"): ?>', $template);
    $template = preg_replace('/<@ ?(if|elseif) ([A-Za-z0-9_]+) \|\| ([A-Za-z0-9_]+) ?!>/','<?php $1($context->get$2() || $context->get$3()): ?>', $template);
    $template = preg_replace('/<@ ?(if|elseif) ([A-Za-z0-9_]+) ?@>/','<?php $1($context->get$2()): ?>', $template);
    $template = preg_replace('/<@ ?with ([A-Za-z0-9_]+) ?@>/', '<?php array_push($contexts, $context); ?><?php $context = $context->get$1(); ?>', $template);
    $template = preg_replace('/<@ ?with ([A-Za-z0-9_]+)\.([A-Za-z0-9_]+) ?@>/', '<?php array_push($contexts, $context); ?><?php $context = $context->get$1()->get$2(); ?>', $template);
    $template = preg_replace('/<@ ?\/with ?@>/', '<?php $context = array_pop($contexts); ?>', $template);
    $template = preg_replace('/<@ ?each :Children ?@>/', '<?php array_push($contexts, $context); ?><?php $pos=0;$first=false;$last=false; $c = $context;$max=sizeof($c);foreach ($c as $key => $context): $pos++;$first=($pos==1);$last=($pos==$max);?>', $template);
    $template = preg_replace('/<@ ?each ([A-Za-z0-9_]+) ?@>/', '<?php array_push($contexts, $context); ?><?php $pos=0;$first=false;$last=false;$c=$context->get$1();$max=sizeof($c);foreach ($c as $key => $context): $pos++;$first=($pos==1);$last=($pos==$max);?>', $template);
    $template = preg_replace('/<@ ?each ([A-Za-z0-9_]+)\.([A-Za-z0-9_]+) ?@>/', '<?php array_push($contexts, $context); ?><?php $pos=0;$first=false;$last=false;$c=$context->get$1()->get$2();$max=sizeof($c);foreach ($c as $key => $context): $pos++;$first=($pos==1);$last=($pos==$max); ?>', $template);
    $template = preg_replace('/<@ ?\/each ?@>/', '<?php endforeach ?><?php $context = array_pop($contexts); ?>', $template);
    return $template;    
  }



  /**
   * Shows the PHP code that is being eval'ed with line numbers
   *
   * @return string
   */
  public function debug() {
    $template = $this->generateTemplate();
    $lines = array ();
    $i = 0;
    foreach(explode("\n", $template) as $l) {
      $i++;      
      $lines[] = "$i. $l";
    }
    return "<pre>".implode("\n",$lines)."</pre>";
  }



  /**
   * Prints the final contents of the template once the PHP has been processed
   *
   * @return string
   */
  public function render() {  	
    $template = $this->generateTemplate();
    $context = $this->context;
    $contexts = array ();
    //echo "--------------------------------\n$template";
    ob_start();    
    	eval($template);    	
    $code = ob_get_contents();
    ob_end_clean();    
    return $code;
  }


}
