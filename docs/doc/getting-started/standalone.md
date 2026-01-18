---
layout: base
title: PHP-ETL - Getting Started
subTitle: 🐘 Standalone 
---

## Introduction

You probably will never run the ETL in a stand-alone mode, but if you do intend to create an adapter
for a different Framework or CMS you need to understand how to start the ETL from "nothing". So this document 
focuses on that aspect. 

That said, this does not mean the ETL can't be run standalone, it's just that the "init" part is not ideal. 

## Writing some code

- Start by installing the necessary dependencies
```sh
composer require oliverde8/php-etl
```

- We will also need a factory to create execution contexts, you can read more about what an execution context is
[here](/doc/01-understand-the-etl/execution-context.html). 
```php
use Oliverde8\Component\PhpEtl\ExecutionContextFactory;

$executionContextFactory = new ExecutionContextFactory();
```

> ##### TIP
> The default provider we use here is very simple and basically bypasses the contexts.
{: .block-tip }

- You will need to initialize the rule applier and all your operation factories.
```php
use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\GenericChainFactory;
use Oliverde8\Component\RuleEngine\RuleApplier;
use Oliverde8\Component\RuleEngine\Rules\Get;
use Oliverde8\Component\RuleEngine\Rules\Implode;
use Oliverde8\Component\RuleEngine\Rules\StrToLower;
use Oliverde8\Component\RuleEngine\Rules\StrToUpper;
use Oliverde8\Component\RuleEngine\Rules\ExpressionLanguage;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\HttpClient;

// Create the rule applier with all available rules
$ruleApplier = new RuleApplier(
    new NullLogger(),
    [
        new Get(new NullLogger()),
        new Implode(new NullLogger()),
        new StrToLower(new NullLogger()),
        new StrToUpper(new NullLogger()),
        new ExpressionLanguage(new NullLogger()),
    ]
);

// Create HTTP client for API operations
$client = HttpClient::create(['headers' => ['Accept' => 'application/json']]);

// Initialize the chain builder with all operation factories
$chainBuilder = new ChainBuilderV2(
    $executionContextFactory,
    [
        new GenericChainFactory(
            \Oliverde8\Component\PhpEtl\ChainOperation\Extract\CsvExtractOperation::class,
            \Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig::class
        ),
        new GenericChainFactory(
            \Oliverde8\Component\PhpEtl\ChainOperation\Extract\JsonExtractOperation::class,
            \Oliverde8\Component\PhpEtl\OperationConfig\Extract\JsonExtractConfig::class
        ),
        new GenericChainFactory(
            \Oliverde8\Component\PhpEtl\ChainOperation\Transformer\RuleTransformOperation::class,
            \Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig::class,
            injections: ['ruleApplier' => $ruleApplier]
        ),
        new GenericChainFactory(
            \Oliverde8\Component\PhpEtl\ChainOperation\Transformer\FilterDataOperation::class,
            \Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig::class,
            injections: ['ruleApplier' => $ruleApplier]
        ),
        new GenericChainFactory(
            \Oliverde8\Component\PhpEtl\ChainOperation\Transformer\CallbackTransformerOperation::class,
            \Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig::class
        ),
        new GenericChainFactory(
            \Oliverde8\Component\PhpEtl\ChainOperation\Transformer\SimpleHttpOperation::class,
            \Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig::class,
            injections: ['client' => $client]
        ),
        new GenericChainFactory(
            \Oliverde8\Component\PhpEtl\ChainOperation\Transformer\SplitItemOperation::class,
            \Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SplitItemConfig::class
        ),
        new GenericChainFactory(
            \Oliverde8\Component\PhpEtl\ChainOperation\Loader\FileWriterOperation::class,
            \Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig::class
        ),
        new GenericChainFactory(
            \Oliverde8\Component\PhpEtl\ChainOperation\Grouping\SimpleGroupingOperation::class,
            \Oliverde8\Component\PhpEtl\OperationConfig\Grouping\SimpleGroupingConfig::class
        ),
        new GenericChainFactory(
            \Oliverde8\Component\PhpEtl\ChainOperation\ChainSplitOperation::class,
            \Oliverde8\Component\PhpEtl\OperationConfig\ChainSplitConfig::class
        ),
        new GenericChainFactory(
            \Oliverde8\Component\PhpEtl\ChainOperation\ChainMergeOperation::class,
            \Oliverde8\Component\PhpEtl\OperationConfig\ChainMergeConfig::class
        ),
        new GenericChainFactory(
            \Oliverde8\Component\PhpEtl\ChainOperation\ChainRepeatOperation::class,
            \Oliverde8\Component\PhpEtl\OperationConfig\ChainRepeatConfig::class
        ),
        new GenericChainFactory(
            \Oliverde8\Component\PhpEtl\ChainOperation\FailSafeOperation::class,
            \Oliverde8\Component\PhpEtl\OperationConfig\FailSafeConfig::class
        ),
        new GenericChainFactory(
            \Oliverde8\Component\PhpEtl\ChainOperation\Transformer\LogOperation::class,
            \Oliverde8\Component\PhpEtl\OperationConfig\Transformer\LogConfig::class
        ),
        new GenericChainFactory(
            \Oliverde8\Component\PhpEtl\ChainOperation\Extract\ExternalFileFinderOperation::class,
            \Oliverde8\Component\PhpEtl\OperationConfig\Extract\ExternalFileFinderConfig::class,
            injections: ['fileSystem' => new \Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem("/")]
        ),
        new GenericChainFactory(
            \Oliverde8\Component\PhpEtl\ChainOperation\Transformer\ExternalFileProcessorOperation::class,
            \Oliverde8\Component\PhpEtl\OperationConfig\Transformer\ExternalFileProcessorConfig::class
        ),
    ],
);
```

> ##### TIP
> We are here initializing all possible operations. Most of the documentation
> will assume you are using Symfony as a framework. You will need to register your factories manually
> if you are not using Symfony.
{: .block-tip }


- Now we can describe our ETL using typed PHP configuration objects instead of YAML. Let's create a simple chain that transforms and outputs data.
```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;

// Create the chain configuration
$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink((new RuleTransformConfig(false))
        ->addColumn('id', [['get' => ['field' => 'ID']]])
        ->addColumn('name', [['get' => ['field' => 'Name']]])
        ->addColumn('email', [['get' => ['field' => 'Email']]])
    )
    ->addLink(new CallBackTransformerConfig(function(DataItem $item) {
        // Dump the data to console
        var_dump($item->getData());
        return $item;
    }))
    ->addLink(new CsvFileWriterConfig('output.csv'));
```

- Build the chain processor from the configuration
```php
$chainProcessor = $chainBuilder->createChain($chainConfig);
```

- Prepare your input data. For a CSV file, wrap it in a DataItem:
```php
$inputData = new ArrayIterator([
    new DataItem(['file' => 'customers.csv'])
]);
```

- Execute the ETL chain
```php
$chainProcessor->process($inputData, []);
```

## Complete Example

Here's a complete standalone ETL script:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\ExecutionContextFactory;
use Oliverde8\Component\PhpEtl\GenericChainFactory;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\RuleEngine\RuleApplier;
use Oliverde8\Component\RuleEngine\Rules\Get;
use Oliverde8\Component\RuleEngine\Rules\ExpressionLanguage;
use Psr\Log\NullLogger;

// Initialize dependencies
$ruleApplier = new RuleApplier(
    new NullLogger(),
    [
        new Get(new NullLogger()),
        new ExpressionLanguage(new NullLogger()),
    ]
);

// Create chain builder
$chainBuilder = new ChainBuilderV2(
    new ExecutionContextFactory(),
    [
        new GenericChainFactory(
            \Oliverde8\Component\PhpEtl\ChainOperation\Extract\CsvExtractOperation::class,
            \Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig::class
        ),
        new GenericChainFactory(
            \Oliverde8\Component\PhpEtl\ChainOperation\Transformer\RuleTransformOperation::class,
            \Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig::class,
            injections: ['ruleApplier' => $ruleApplier]
        ),
        new GenericChainFactory(
            \Oliverde8\Component\PhpEtl\ChainOperation\Transformer\CallbackTransformerOperation::class,
            \Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig::class
        ),
        new GenericChainFactory(
            \Oliverde8\Component\PhpEtl\ChainOperation\Loader\FileWriterOperation::class,
            \Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig::class
        ),
    ],
);

// Build the ETL chain
$chainConfig = (new ChainConfig())
    ->addLink(new CsvExtractConfig())
    ->addLink((new RuleTransformConfig(false))
        ->addColumn('customer_id', [['get' => ['field' => 'ID']]])
        ->addColumn('customer_name', [['get' => ['field' => 'Name']]])
    )
    ->addLink(new CallBackTransformerConfig(function(DataItem $item) {
        echo "Processing: " . json_encode($item->getData()) . "\n";
        return $item;
    }))
    ->addLink(new CsvFileWriterConfig('output.csv'));

$chainProcessor = $chainBuilder->createChain($chainConfig);

// Execute
$chainProcessor->process(
    new ArrayIterator([new DataItem(['file' => 'input.csv'])]),
    []
);

```
