---
layout: base
title: PHP-ETL - Cook Books
subTitle: Using Sub Chains
width: large
---

### Using subchains

There will be cases where the chain description can become quite repetitive, let's take the following example
from Chapter 1 - [Splittin/Forking](/doc/10-examples/120-split-the-chain.html).

In that example we have split our customer.csv files into 2 files, one with the customers subscribed to the newsletter
and one with those not subscribed. We do not do any additional process to change the structure of the data.

Let's now imagine we would like to extract only the firstName and Lastname from the csv file for the subscribed customers.
The resulting chain would look like: 

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainSplitConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

// First branch: subscribed customers with transformation
$subscribedBranch = new ChainConfig();
$subscribedBranch
    ->addLink(new FilterDataConfig(
        rules: [['get' => ['field' => 'IsSubscribed']]],
        negate: false
    ))
    ->addLink(new RuleTransformConfig(
        columns: [
            'FirstName' => [
                'rules' => [['get' => ['field' => 'FirstName']]]
            ],
            'LastName' => [
                'rules' => [['get' => ['field' => 'LastName']]]
            ]
        ],
        add: false
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

$chainConfig->addLink(new ChainSplitConfig(
    branches: [$subscribedBranch, $unsubscribedBranch]
));
```

In order to do the same for both subscribed & unsubscribed customer we would need to duplicate the whole transformation
config. That would be quite inefficient. Also this is a very simple case, if we wanted to add grouping and more
transforms it makes the amount of duplications even more important.

With the new v2 paradigm, we can use reusable ChainConfig objects (functions or factory methods) to avoid duplication:


{% capture description %}
We can create a function that returns a configured ChainConfig for the necessary transformations.
{% endcapture %}
{% capture code %}
```php
function createCustomTransform(): ChainConfig
{
    $config = new ChainConfig();
    $config->addLink(new RuleTransformConfig(
        columns: [
            'FirstName' => [
                'rules' => [['get' => ['field' => 'FirstName']]]
            ],
            'LastName' => [
                'rules' => [['get' => ['field' => 'LastName']]]
            ]
        ],
        add: false
    ));
    return $config;
}
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

{% capture description %}
We can then use this function to create transformation chains for both branches, avoiding duplication.
{% endcapture %}
{% capture code %}
```php
// First branch: subscribed customers
$subscribedBranch = new ChainConfig();
$subscribedBranch
    ->addLink(new FilterDataConfig(
        rules: [['get' => ['field' => 'IsSubscribed']]],
        negate: false
    ));
// Add the reusable transformation
foreach (createCustomTransform()->getLinks() as $link) {
    $subscribedBranch->addLink($link);
}
$subscribedBranch->addLink(new CsvFileWriterConfig('subscribed.csv'));

// Second branch: unsubscribed customers
$unsubscribedBranch = new ChainConfig();
$unsubscribedBranch
    ->addLink(new FilterDataConfig(
        rules: [['get' => ['field' => 'IsSubscribed']]],
        negate: true
    ));
// Add the same reusable transformation
foreach (createCustomTransform()->getLinks() as $link) {
    $unsubscribedBranch->addLink($link);
}
$unsubscribedBranch->addLink(new CsvFileWriterConfig('unsubscribed.csv'));
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

**The following benefits apply for reusable ChainConfig objects:**
- Reusable chains can have multiple operations as with a normal chain.
- You can use PHP functions, factory methods, or classes to create reusable configurations.
- Reusable chains can themselves use other reusable chains, enabling multiple levels of composition.
- Full IDE support with autocompletion and type checking.
- Easy to test and refactor using standard PHP patterns.

#### Complete Code

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainSplitConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;

// Reusable transformation function
function createCustomTransform(): ChainConfig
{
    $config = new ChainConfig();
    $config->addLink(new RuleTransformConfig(
        columns: [
            'FirstName' => [
                'rules' => [['get' => ['field' => 'FirstName']]]
            ],
            'LastName' => [
                'rules' => [['get' => ['field' => 'LastName']]]
            ]
        ],
        add: false
    ));
    return $config;
}

// Main chain configuration
$chainConfig = new ChainConfig();
$chainConfig->addLink(new CsvExtractConfig());

// First branch: subscribed customers
$subscribedBranch = new ChainConfig();
$subscribedBranch->addLink(new FilterDataConfig(
    rules: [['get' => ['field' => 'IsSubscribed']]],
    negate: false
));
foreach (createCustomTransform()->getLinks() as $link) {
    $subscribedBranch->addLink($link);
}
$subscribedBranch->addLink(new CsvFileWriterConfig('subscribed.csv'));

// Second branch: unsubscribed customers
$unsubscribedBranch = new ChainConfig();
$unsubscribedBranch->addLink(new FilterDataConfig(
    rules: [['get' => ['field' => 'IsSubscribed']]],
    negate: true
));
foreach (createCustomTransform()->getLinks() as $link) {
    $unsubscribedBranch->addLink($link);
}
$unsubscribedBranch->addLink(new CsvFileWriterConfig('unsubscribed.csv'));

// Add split operation with both branches
$chainConfig->addLink(new ChainSplitConfig(
    branches: [$subscribedBranch, $unsubscribedBranch]
));

// Create and execute the chain
$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem(['file' => 'customers.csv'])]),
    []
);
```