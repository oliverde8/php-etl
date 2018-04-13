
## Creating a chain using code.

### Operations

Each operation in the ETL Chain will take a `ItemInterface` in input and will need to return 
the same or another `ItemInterface`. 

### Transform - Callback Transformer Operation

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

### Transform - Rule Transform Operation

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

We can have nested columns using `/` in the key : 

```yaml
rules:
    label/fr_FR:
        - [{get : {field: ['fr_FR', 'label']}]
```

We can also have dynamic columns. 

```yaml
rules:
    label-{@context/locales}:
        - [{get : {field: ['@context/locales', 'label']}]
```

Basically a column will be generated for each `locale` in the context.

So if we have `[fr_FR, 'en_GB]` in our context, it will do the equivalent of the fallowing code.
```php
<?php
$result['label-fr_FR'] = $data['fr_FR']['label'];
$result['label-en_GB'] = $data['en_GB']['label'];
```

We can have multiple variables in our dynamic columns.

```yaml
rules:
    label-{@context/scopes}/{@context/locales}:
        - [{get : {field: ['@context/locales', 'label']}]
```

To use these rules in our ETL chain we will need to create our RuleTransformationOperation : 

```php
<?php

$operation = new RuleTransformOperation($ruleApplier, Yaml::parse('my-rules.yml'), true);
```

The third parameter tells us to add the new `uid` field to the existing data, instead of replacing the whole data.

[See more in the RuleTransformer docs](docs/RuleEngine.md)

### Transform - Simple Grouping operation

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

### Transform - Chain Split

Chain split allows to redirect the result at a certain step of the chain towars a new chain. 

**Exemple :** The input file we are reading needs to be split into 2 files. 
Columns A, B, C goes to file1 and columns B, C, D to file 2.

The columns B & C are in common, it is therefore not logical to read the file 2 times and to the transformations 2 times.
Multiple operations in our chain will transform the data, and prepare columns A, B, C, and D. 

Here we will split the chain, the original chain will receive the 4 columns as if there was no split. The new chain will
get in input the 4 columns as well. Now both chains can do specific transformations. 

```php
<?php

// Execute multiple common operations.
$mainOp['main_1'] = new RuleTransformOperation($ruleApplier, Yaml::parse('transform1.yml'), true);
$mainOp['main_2'] = new RuleTransformOperation($ruleApplier, Yaml::parse('transform2.yml'), true);
//....

// Split at this point to get a second result.
$mainOp['split'] = new ChainSplitOperation([new ChainProcessor([/* List of other operations ... */])]);

// Continue processing data.
$mainOp['main_2'] = new RuleTransformOperation($ruleApplier, Yaml::parse('transform3.yml'), true);
```

### Load - File Operation

Can be used to write the data into a file, using the `FileWriterInterface`. 

**Exemple, write in a cs file :**
```php
<?php
$operation = new FileWriterOperation(new Writer(__DIR__ . '/exemples/output.csv'));
```