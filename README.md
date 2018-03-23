# PHP ETL Chain

[![Build Status](https://travis-ci.org/oliverde8/php-etl.svg?branch=master)](https://travis-ci.org/oliverde8/php-etl)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/oliverde8/php-etl/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/oliverde8/php-etl/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/oliverde8/php-etl/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/oliverde8/php-etl/?branch=master)
[![Donate](https://img.shields.io/badge/paypal-donate-yellow.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=oliverde8@gmail.com&lc=US&item_name=php-etl&no_note=0&cn=&curency_code=EUR&bn=PP-DonationsBF:btn_donateCC_LG.gif:NonHosted)

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

### Operations

Each operation in the ETL Chain will take a `ItemInterface` in input and will need to return 
the same or another `ItemInterface`. 

### Callback Transformer Operation

This allows to have a method that will be called with the data that needs to be transformed.

**Exemple :**

In this example we will create a uid that can be used later.

```php
<?php
$operation = new CallbackTransformerOperation(function (DataItemInterface $item, &$context) {
    $data = $item->getData();
    $data['uid'] = implode(
        '_',
        [
            'PREFIX',
            AssociativeArray::getFromKey($data, 'ID_PART1'),
            AssociativeArray::getFromKey($data, 'ID_PART2'),
        ]
    );
    
    return new DataItem($data);
});
?>
```

### Rule Transform Operation

This allows us to describe simple transformations using a configuration. So let's do the same we did on. 

Let's first write our rule in a yml file as it's easier to read.

**Exemple :**

```yaml
rules:
    uid:
        - implode:
            values:
              - 'PREFIX'
              - [{get : {field: "ID_PART1"}}]
              - [{get : {field: "ID_PART2"}}]
            with: "_"
```

The get rule uses the Simple AssociativeArray library. So to get nested elements 
you can send a table instead of a string.

```yaml
rules:
    uid:
        - implode:
            values:
              - 'PREFIX'
              - [{get : {field: ['toto', 'ID_PART1']}]
              - [{get : {field: "ID_PART2"}}]
            with: "_"
```

We can now create our operation. 

```php
<?php

$operation = new RuleTransformOperation($ruleApplier, Yaml::parse('my-rules.yml'), true);
```

The third parameter tells us to add the new `uid` field to the existing data, instead of replacing the whole data.

[See more in the RuleTransformer docs](docs/RuleEngine.md)

### Simple Grouping operation

This will allow multiple lines to be combined in one single line.

> Note1 : **This is operation uses memory** The ETL will work "line by line" and therefore will not need much mermoy.
> This operation on the other hand stores everything in memory. 

> Note2 :  a custom operation using the database or so can be put in place to optimize!

Each opeartion after the grouping will receive grouped lines. 

**Example :**
```php
<?php
$operations = new SimpleGroupingOperation(['uid']);
```

So the fallowing data will be read in each item received by the grouping operation.
```php
<?php

$item1 = ['uid' => 1, 'data' => 5];
$item2 = ['uid' => 1, 'data' => 7];
$item3 = ['uid' => 2, 'data' => 6];
$item4 = ['uid' => 2, 'data' => 12];
```

The fallowing operation will receive the items containing the fallowing data.
```php
<?php
$item1 = [
    ['uid' => 1, 'data' => 5],
    ['uid' => 1, 'data' => 7],
];

$item2 = [
    ['uid' => 2, 'data' => 6],
    ['uid' => 2, 'data' => 12],
];
```

> A operation will always have an Item in entry and output an Item. GroupedItems will 
> always be split apart automatically.

### File Writer Operation

Can be used to write the data into a file, using the `FileWriterInterface`. 

**Exemple, write in a cs file :**
```php
<?php
$operation = new FileWriterOperation(new Writer(__DIR__ . '/exemples/output.csv'));
```

## End to end example

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
```

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


# TODO

This was originally done as a pock, but ended up being a nice reusable idea.

* More cleanup & comment

* Rule Engine : 
    * Think of a way to have generic dynamic columns, for handling multi locales for example. This might not needed
    as in the ETL it can be handle with a custom operation applying the ruleset for each locale.

* Make a builder to build the `chain` description easily in yaml or so. 
It might be a better idea to do this in the SF Bundle rather then here. Requires some thinking.