---
layout: base
title: PHP-ETL - Cook Books
subTitle: Making your chains configurable
width: large
---

{% capture description %}
You are able to configure through the input the names of the files that are being read.
{% endcapture %}
{% capture code %}

```php
use Oliverde8\Component\PhpEtl\Item\DataItem;

$chainProcessor->process(
    new ArrayIterator([new DataItem(['file' => __DIR__ . "/customers.csv"])]),
    []
);
```

{% endcapture %}
{% include block/etl-step.html code=code description=description %}

{% capture description %}
But we might need to configure some operations independently from the input. For example the name of the csv output file.
{% endcapture %}

{% capture code %}

```php
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$chainConfig->addLink(new CsvFileWriterConfig('output.csv'));
```

{% endcapture %}
{% include block/etl-step.html code=code description=description %}

{% capture description %}

The name "output.csv" is hardcoded here. But we can make this dynamic with PHP variables. Simply define your
configuration parameters and use them when creating the operation configs.
{% endcapture %}

{% capture code %}

```php
// Define configuration
$outputFileName = 'configured-output.csv';

// Use in configuration
$chainConfig->addLink(new CsvFileWriterConfig($outputFileName));
```

{% endcapture %}
{% include block/etl-step.html code=code description=description %}

You can pass configuration values from external sources (environment variables, config files, etc.) and use them when building your chain:

{% capture column1 %}

## 🐘 Standalone

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;

// Get configuration from your source
$config = [
    'outputFileName' => 'configured-output.csv'
];

// Build the chain with configuration
$chainConfig = new ChainConfig();
// ... add operations using $config values
$chainConfig->addLink(
    new CsvFileWriterConfig($config['outputFileName'])
);

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem(['file' => './customers.csv'])]),
    []
);
```

{% endcapture %}

{% capture column2 %}

## 🎵 Symfony

```sh
./bin/console etl:execute myetl "['./customers.csv']" "{'outputfile': {'name': 'configured-output.csv'}}"
```

{% endcapture %}
{% include block/2column.html column1=column1 column2=column2 %}

### Complete Code

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

// Configuration from your source (env, config file, etc.)
$config = [
    'outputFileName' => 'configured-output.csv'
];

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink(new RuleTransformConfig(
        columns: [
            'Name' => [
                'rules' => [
                    ['implode' => [
                        'values' => [
                            [['get' => ['field' => 'FirstName']]],
                            [['get' => ['field' => 'LastName']]]
                        ],
                        'with' => ' '
                    ]]
                ]
            ],
            'SubscriptionStatus' => [
                'rules' => [
                    ['get' => ['field' => 'IsSubscribed']]
                ]
            ]
        ],
        add: false
    ))
    ->addLink(new CsvFileWriterConfig($config['outputFileName']));

// Create and execute the chain
$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem(['file' => 'customers.csv'])]),
    []
);
```
