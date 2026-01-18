---
layout: base
title: PHP-ETL - Cook Books
subTitle: Filtering data
width: large
---

{% capture description %}
We can also filter data preventing some of it from being propagated through all the chain, in our example
it will prevent unsubscribed customers from being written in our final csv file. So we can add this operation to our chain.

The rule engine is used for the filtering. If the rule returns false, 0, empty string or null then the item **will not
be propagated**. We can also inverse this rule by setting `negate: true`, in this case the rule needs to return
false for the item **to be propagated**.

This might seem limiting but the rule engine does support SymfonyExpressions which opens a whole lot of flexibility.
{% endcapture %}
{% capture code %}

```php
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;

$chainConfig->addLink(new FilterDataConfig(
    rules: [['get' => ['field' => 'IsSubscribed']]],
    negate: false
));
```

{% endcapture %}
{% include block/etl-step.html code=code description=description %}

## Complete Configuration

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink(new FilterDataConfig(
        rules: [['get' => ['field' => 'IsSubscribed']]],
        negate: false
    ))
    ->addLink(new CsvFileWriterConfig('output.csv'));

// Create and execute the chain
$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem(['file' => 'customers.csv'])]),
    []
);
```
