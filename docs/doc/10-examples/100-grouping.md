---
layout: base
title: PHP-ETL - Cook Books
subTitle: Grouping / Aggregation
width: large
---

A second example we can work on is to write a json file where customers are grouped based on their subscription state.
We will write this in json as its more suited to understand what we are doing.

{% capture description %}
Let's start by reading our csv file
{% endcapture %}
{% capture code %}

```php
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;

$chainConfig->addLink(new CsvExtractConfig());
```

{% endcapture %}
{% include block/etl-step.html code=code description=description %}

{% capture description %}
We will use the `SimpleGroupingConfig` operation for this. **This operation needs to put all the data in memory
and should therefore be used with caution.**

We have a single **grouping-key**, we can make more complex grouping operations, by grouping by subscription status and
gender for example.

Grouping identifier allows us to remove duplicates, if we had customer emails we could have used
that information for example.
{% endcapture %}
{% capture code %}

```php
use Oliverde8\Component\PhpEtl\OperationConfig\Grouping\SimpleGroupingConfig;

$chainConfig->addLink(new SimpleGroupingConfig(
    groupingKey: ['IsSubscribed'],
    groupIdentifier: []
));
```

{% endcapture %}
{% include block/etl-step.html code=code description=description %}

{% capture description %}
We will also use the JSON file writer operation.

This works like the csv file, but is more suited for complex multi level datas as we have after the grouping.
{% endcapture %}
{% capture code %}

```php
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$chainConfig->addLink(new CsvFileWriterConfig(
    file: 'output.json',
    fileFormat: 'json'
));
```

{% endcapture %}
{% include block/etl-step.html code=code description=description %}

## Complete Configuration

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Grouping\SimpleGroupingConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink(new SimpleGroupingConfig(
        groupingKey: ['IsSubscribed'],
        groupIdentifier: []
    ))
    ->addLink(new CsvFileWriterConfig(
        file: 'output.json',
        fileFormat: 'json'
    ));

// Create and execute the chain
$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem(['file' => 'customers.csv'])]),
    []
);
```
