# Bedrock YAML

## A PHP utility for traversing and rendering YAML data

## Traversing Data

### We'll use the following YAML data as an example:

```
LivingThings:
  Animals:
    Mammals:
      Aquatic:
        Whales:
          Endangered: true
          TopSpeed: 25mph
        Dolphin:
          Endangered: false
          TopSpeed: 40mph
      Land:
        Dog:
          Domesticated: true
          Legs: 4
        Gorilla:
          Domesticated: false
          Legs: 2
    Fish:
      Freshwater:
        - Talapia
        - Bass
      Marine:
        - Clownfish
        - Tang
        - Trigger
    Insects:
      Fly:
        Annoying: true        
      Mosquito:
        Annoying: true
      Butterfly:
        Annoying: false
        
  Plants:
    Aquatic:
      - Seaweed
      - Kelp
    Land:
      - Oak
      - Pine
      - Maple

```
### First, we include the Bedrock boostrap script, and instantiate a new BedrockYML object.
```php
  <?php
  require_once("bedrock.php");
  $yml = new BedrockYAML("/path/to/yaml.yml");
```

### Now we can traverse the yaml as an object
```php
  <?php
  echo $yml->getLivingThings()->getAnimals()->getMammals()->getAquatic()->getWhales()->getTopSpeed();
  // "25mph"
```
### We can also traverse using Dot.Separated.Syntax
```php
  <?php
  echo $yml->get('LivingThings.Plants')->getAquatic()->first();
  // "Seaweed"
```
### Iterating is no problem.
```php
  <?php
  foreach($yml->getLivingThings()->getAnimals()->getInsects() as $insect) {
    if(!$insect->getAnnoying()) {
      echo $insect->getKey();
    }
  }
  // "Butterfly"
  
  $aquatic_plants = $yml->getLivingThings()->getPlants()->getAquatic();
  echo $acuatic_plants->getParentNode()->getLand()->last();
  // "Maple"
```

### It is also possible to create custom classes for specific nodes
Just create a class with the name of the node prefaced by "Bedrock"

```php
<?php
class BedrockInsects extends BedrockNode {
  protected $iteratorClass = "BedrockInsects_Iterator";
}


class BedrockInsects_Iterator extends BedrockNode_Iterator {
	protected $iteratorNodeClass = "BedrockInsect";
}


class BedrockInsect extends BedrockNode {
	public function getShouldIKillIt() {		
		return $this->getAnnoying() ? "yes" : "no";
	}
}

foreach($yml->getLivingThings()->getAnimals()->getInsects() as $insect) {
  echo $insect->getShouldIKillIt();
}
// "yesyesno";
```

## Rendering Data

The BedrockTemplate class allows you to create a template for rendering the data into HTML or any other text format.

### Example template
```
<# with LivingThings #>
<p>Aquatic mammals are very interesting.
  <# with Animals.Mammals #>
		<# each Aquatic #>
			<#= :Name #> are 
				<# if Endangered #>
					rare because they are endangered
				<# else #>
					all over the ocean
				<# /if #>
			and can reach a top speed of <@= TopSpeed @>.
		<# /each #>
	<# /with #>
</p>
<p>Some animals are friendly with humans. They include:
	<ul>
		<# with Animals.Mammals #>
			<# each Land #>
				<# if Domesticated #>
					<li><#= :Name #></li>
				<# /if #>
			<# /each #>
		<# /with #>
	</ul>
</p>
<p>Other animals deserve to die because they're annoying. If you see these, squash 'em.</p>
	<ul>
		<# each Animals.Insects #>		
			<# if ShouldIKillIt == "yes" #>
			<li><#= :Name #></li>
			<# /if #>
		<# /each #>
	</ul>
</p>
<# /with #>
```
### Processing the template
```php
  <?php
  $yml = new BedrockYAML("/path/to/my.yml");
  $template = new BedrockTemplate("/path/to/template.bedrock");
  $template->bind($yml);
  echo $template->render();
```

### Result
```
Aquatic mammals are very interesting. Whales are rare because they are endangered and can reach a top speed of 25mph. Dolphin are all over the ocean and can reach a top speed of 40mph.

Some animals are friendly with humans. They include:

Dog
Cat
Other animals deserve to die because they're annoying. If you see these, squash 'em.

1. Fly
2. Mosquito
```
### Debugging
This will show you the PHP code, with line numbers, that is being eval'ed if you get a parse error.
```php
  <?php
  echo $template->debug();
```