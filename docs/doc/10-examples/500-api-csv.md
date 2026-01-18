---
layout: base
title: PHP-ETL - Cook Books
subTitle: With Context - Api to CSV
width: large
---

### With Context - Api to CSV

This example uses **execution contexts**, which isolate each ETL execution in its own directory. 
If you're not familiar with execution contexts, please read [Execution Context - Why to have an execution context & what it does](/doc/01-understand-the-etl/execution-context.html).

{% capture description %}
The chain definition is identical to our previous [definition](/doc/10-examples/150-api-csv.html) one without a context. 
It's the end results that changes, as now our file is created within the unique context directory.
{% endcapture %}
{% capture code %}
```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SplitItemConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Model\PockExecution;

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new SimpleHttpConfig(
        url: 'https://63b687951907f863aaf90ab1.mockapi.io/test',
        method: 'GET',
        responseIsJson: true,
        optionKey: null,
        responseKey: null,
        options: [
            'headers' => ['Accept' => 'application/json']
        ]
    ))
    ->addLink(new SplitItemConfig(
        keys: ['content'],
        singleElement: true
    ))
    ->addLink(new CsvFileWriterConfig('output.csv'));

// Create and execute the chain with execution context
$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem([])]),
    [
        'etl' => [
            'execution' => new PockExecution(new DateTime())
        ]
    ]
);
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

If we wish to "make" available outside the context we will need to use specific operations to achieve that. If we wish
to access a file outside the context we will also need to import the file in the context first. We will see this in
other examples.
