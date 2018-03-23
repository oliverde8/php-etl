#Rule Engine - Data Transformation
 
Is a mini transformation engine that transform an associative array into another associative array 
using various sets of rules.
 
## Example usage :

Let's consider the fallowing data : 
```php
<?php
$data = [
    'country' => 'Fr',
    'locale' => 'fr',
    'identifier' => 'myID',
    'name' => 'Label of product'  
];
```

and we would like to import it in akeneo, we therefore need the end data to look like this :
```php
<?php
$finalData = [
    'sku' => 'myID',
    'name-fr_FR' => 'Label of product',
    'name-en_US' => ''
];
```

This is a edge case but the transformer can still handle it. 

```yaml
# We will need to generate this column even if we don't use it.
locale:
    rules:
        - implode:
            values:
              - [{str_lower: {value: [{get : {field: "locale"}}]}}]
              - [{str_upper: {value: [{get : {field: "country"}}]}}]
            with: "_"

sku: 
    rules:
        - strtolower: 
            value: [{value_getter : {field: "identifier"}}]  

name-fr_FR:
    rules:
        - condition:
          if: [{get: {field: locale}}]
          value: fr_FR
          then:  [{get : {field: "name"}}]
          else: ""
          
name-en_US:
  rules:
      - condition:
        if: [{get: {field: locale}}]
        value: "en_US"
        then:  [{get : {field: "name"}}]
        else: ""    
```

>Let's note that the `value` of conditions should be a rule, but if the rule engine receives a string it considers it's a 
constant. 

> Also, let's note that rules takes an array, of rules. Basially it will iterate over each rule until a not empty 
response is returned.

We can now put it all together. 

```php
<?php

$rules = [
    new Oliverde8\Component\RuleEngine\Rules\Condition(new \Psr\Log\NullLogger()),
    new Oliverde8\Component\RuleEngine\Rules\Implode(new \Psr\Log\NullLogger()),
    new Oliverde8\Component\RuleEngine\Rules\StrToLower(new \Psr\Log\NullLogger()),
    new Oliverde8\Component\RuleEngine\Rules\Get(new \Psr\Log\NullLogger()),
];

$columns = \Symfony\Component\Yaml\Yaml::parse('my-rules.yml');

$ruleApplier = new Oliverde8\Component\RuleEngine\RuleApplier(new \Psr\Log\NullLogger(), $rules, true);

$finalData = $ruleApplier->apply($data, ['id' => 'myCustomId']);
``` 