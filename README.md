# PHP ETL Chain

[![Build Status](https://travis-ci.org/oliverde8/php-etl.svg?branch=master)](https://travis-ci.org/oliverde8/php-etl)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/oliverde8/php-etl/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/oliverde8/php-etl/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/oliverde8/php-etl/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/oliverde8/php-etl/?branch=master)
[![Donate](https://img.shields.io/badge/paypal-donate-yellow.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=oliverde8@gmail.com&lc=US&item_name=php-etl&no_note=0&cn=&curency_code=EUR&bn=PP-DonationsBF:btn_donateCC_LG.gif:NonHosted)
[![Latest Stable Version](https://poser.pugx.org/oliverde8/php-etl/v/stable)](https://packagist.org/packages/oliverde8/php-etl)
[![Latest Unstable Version](https://poser.pugx.org/oliverde8/php-etl/v/unstable)](https://packagist.org/packages/oliverde8/php-etl)
[![License](https://poser.pugx.org/oliverde8/php-etl/license)](https://packagist.org/packages/oliverde8/php-etl)

Php etl is a ETL that will allow transformation of data in a simple and efficient way.

It comes in 2 components : 

## Rule engine

The rule engine allows to have configuration based transformations to transform a particular data. 
This is integrated inside the ETL with the `RuleTransformOperation`. 

The rule engine can be used in standalone, [see docs](docs/RuleEngine.md)

## Creating a chain. 

A ETL chain is described by `links`, we call those operations. 

There are a few operations pre-coded in the library. You can create you own. The entry of a ETL chain is 
a standard php `\Iterator`. 

There are 2 ways of writing a chain, either you code it; or you write describe the chain in a yaml file. 

The fallowing 2 exemples does takes the same input and returns the same output. The only thing that changes is the
way it's done.

First of all you need to inizialize all the objects. Hopefully I will have a Symfony bundle around to have a 
easier to use package.

```php
<?php
$ruleApplier = new \Oliverde8\Component\RuleEngine\RuleApplier(
    new \Psr\Log\NullLogger(),
    [
        new \Oliverde8\Component\RuleEngine\Rules\Get(new \Psr\Log\NullLogger()),
        new \Oliverde8\Component\RuleEngine\Rules\Implode(new \Psr\Log\NullLogger()),
        new \Oliverde8\Component\RuleEngine\Rules\StrToLower(new \Psr\Log\NullLogger()),
        new \Oliverde8\Component\RuleEngine\Rules\StrToUpper(new \Psr\Log\NullLogger()),
    ]
);


$builder = new \Oliverde8\Component\PhpEtl\ChainBuilder();
$builder->registerFactory(new RuleTransformFactory('rule-engine-transformer', RuleTransformOperation::class, $ruleApplier));
$builder->registerFactory(new SimpleGroupingFactory('simple-grouping', SimpleGroupingOperation::class));
$builder->registerFactory(new CsvFileWriterFactory('csv-write', FileWriterOperation::class));
```


### Coding a ETL Chain

You can see find here more information [here](docs/ChainCoded.md).

First let's prepare 2 transformations one to prepare for grouping.

**transform1.yml**
```yaml
rules:
    uid:
        - implode:
            values:
              - 'PREFIX'
              - [{get : {field: 'ID_PART1'}}]
              - [{get : {field: "ID_PART2"}}]
            with: "_"
```

And now the second one to finalize the data.

**transform2.yml**
```yaml
rules:
    uid: # Fetching the uid of the first element!
        - get : {field: [0, 'uid']}
        
    label: # Fetching te label from the first if available and if not the second item.
        - get : {field: [0, 'label']}
        - get : {field: [1, 'label']}
        # ...
    # ...
```

```php
<?php
$inputIterator = new Csv(__DIR__  . '/exemples/input.csv');

$operations = [];

// Prepare a `key` so we can properly group the results.
$operations[] = new RuleTransformOperation($ruleApplier, Yaml::parse('transform1.yml'), true);

// Group multiple identical lines.
$operations[] = new SimpleGroupingOperation(['uid']);

// Removing all unessery columns. just keep what wee need and flatten the result after the grouping.
$operations[] = new RuleTransformOperation($ruleApplier, Yaml::parse('transform2.yml'), false);

// Now write the results
$operations[] = new FileWriterOperation(new Writer(__DIR__ . '/exemples/output.csv'));

// We can continue doing other transformations and writing results.
// ....


// Let's process our input file..
$chainProcessor = new ChainProcessor($operations);
$context = [];
$chainProcessor->process($inputIterator, $context);
```

### Configuring a ETL Chain

Let's create a yml file describing the transformation.

```yaml
transform1:
  operation: rule-engine-transformer
  options:
    add: true
    columns:
      uid:
        - implode:
            values:
              - 'PREFIX'
              - [{get : {field: 'ID_PART1'}}]
              - [{get : {field: "ID_PART2"}}]
            with: "_"

group:
  operation: simple-grouping
  options:
    grouping-key: [sku]
    group-identifier: [locale]

transform2:
  operation: rule-engine-transformer
  options:
    add: false
    columns:
        uid: # Fetching the uid of the first element!
            - get : {field: [0, 'uid']}
            
        label: # Fetching te label from the first if available and if not the second item.
            - get : {field: [0, 'label']}
            - get : {field: [1, 'label']}

write:
  operation: csv-write
  options:
    file: exemples/output.csv
```

Now we can run this : 

```php
<?php
$builder = new ChainBuilder();
$chainProcessor = $builder->buildChainProcessor(Yaml::parse(file_get_contents(__DIR__ . '/exemples/etl_chain.yml')));
$context = [];

$chainProcessor->process($inputIterator, $context);
```

As you can see we have simply used the chain builder instead of creating each operation manually.

## Creating you own operations. 

Before creating your own operations you need to understand how the system works. 

A `ItemInterface` will travel througth the chain. There are multiple different type of items that will 
have different effects. 

* **GroupedItem** Will be returned by grouping operations, these will be splitted back into individual DataItems 
automatically.

* **ChainBreakItem** can be returned by an operation when it wishes to stop the chain. For exemple an operation could filter unwanted data.

* **DataItem** is the most common item, it will trigger the `processData` method of your operation.

* **ChainStopItem** This item will travel thought the chain, to let now each operation that the chain has stopped. 
It will trigger the `processStop` method.

If an operation don't have the appropriate method to handle a particular Item, the item will simply skip the Operation.

If you wish for you operation to be usable in with the configuration builder you will also need to create a Factory for it.

# FAQ

* **Why isn't there a database extract ?**
PHP-Etl is a library, not meant to be used standalone, it is meant to be used inside symfony, or magento projects. 
Each of these framework/cms's have their own way of handling the database. I do plan to make a Symfony bundle at one 
point which could have doctrine extractor. 

* **Is there a validaiton of the chain configuration ?**
Yes sir there is. you will get an error like this : 
```
There was an error building the operation 'simple-grouping' : 
 - "grouping-key" : This field is missing.
 - "file" : This field was not expected.
```

# TODO

* ChainBuilder
    * Write unit tests.
    
* Create a few more generic rules. 
    * Data formatting 
    * Numeric formatter
    * ...
    
* The Condition rules has very few operations.

* Improve docs.