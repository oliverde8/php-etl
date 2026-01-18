---
layout: base
title: PHP-ETL - Cook Books
subTitle: Split/Fork the chain
width: large
---

In our next example

- One file containing all the customers
- A second file containing unsubscribed customers
- A third file with subscribed customers.

{% capture description %}
To achieve this we will use the split operation. This operation creates multiple new chains linked to the first chain.
The result of these new chains are not attached to the main chain. So if we do any filtering in one of these
**branches** as they are called, the filtering will not be visible on the main branch.

For our example, the main branch will be used to write all customers, this is very similar to what we did in the
first example. But before writing the files we will add a split operation to create 2 new branches. 1 branch will
filter to get subscribed customers and write them. The second branch will filter to get un subscribed customers and
write them.
{% endcapture %}

{% capture code %}

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainSplitConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

// First branch: subscribed customers
$subscribedBranch = new ChainConfig();
$subscribedBranch
    ->addLink(new FilterDataConfig(
        rules: [['get' => ['field' => 'IsSubscribed']]],
        negate: false
    ))
    ->addLink(new CsvFileWriterConfig('subscribed.csv'));

// Second branch: unsubscribed customers
$unsubscribedBranch = new ChainConfig();
$unsubscribedBranch
    ->addLink(new FilterDataConfig(
        rules: [['get' => ['field' => 'IsSubscribed']]],
        negate: true
    ))
    ->addLink(new CsvFileWriterConfig('unsubscribed.csv'));

// Add split operation with both branches
$chainConfig->addLink(new ChainSplitConfig(
    branches: [$subscribedBranch, $unsubscribedBranch]
));
```

{% endcapture %}
{% include block/etl-step.html code=code description=description %}

### Complete Code

{% capture column1 %}

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainSplitConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$chainConfig = new ChainConfig();
$chainConfig->addLink(new CsvExtractConfig());

// First branch: subscribed customers
$subscribedBranch = new ChainConfig();
$subscribedBranch
    ->addLink(new FilterDataConfig(
        rules: [['get' => ['field' => 'IsSubscribed']]],
        negate: false
    ))
    ->addLink(new CsvFileWriterConfig('subscribed.csv'));

// Second branch: unsubscribed customers
$unsubscribedBranch = new ChainConfig();
$unsubscribedBranch
    ->addLink(new FilterDataConfig(
        rules: [['get' => ['field' => 'IsSubscribed']]],
        negate: true
    ))
    ->addLink(new CsvFileWriterConfig('unsubscribed.csv'));

// Add split operation
$chainConfig->addLink(new ChainSplitConfig(
    branches: [$subscribedBranch, $unsubscribedBranch]
));

// Write all customers to main output
$chainConfig->addLink(new CsvFileWriterConfig('output.csv'));

// Create and execute the chain
$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem(['file' => 'customers.csv'])]),
    []
);
```

{% endcapture %}
{% capture mermaid %}
flowchart TB
S1[Read CSV] -->|Subscribed| S2{Split Step}
S1 -->|UnSubscribed| S2

S2 -->|Susbscribed| S2A1(Filter Subscribed)
S2 -->|UnSubscribed| S2A1

S2 -->|Susbscribed| S2B1(Filter UnSubscribed)
S2 -->|UnSubscribed| S2B1

subgraph SubFlow
S2A1 -->|Susbscribed| S2A2(Write Subscribed)

S2B1 -->|UnSubscribed| S2B2(Write UnSubscribed)
end

S2 --->|Susbscribed| S3(Write Both)
S2 --->|UnSubscribed| S3
{% endcapture %}

{% capture column2 %}
{% include block/mermaid.html mermaid=mermaid %}
{% endcapture %}

{% include block/2column.html column1=column1 column2=column2 %}
