---
layout: base
title: PHP-ETL - Operations
subTitle: Building Blocks - Chain Merge
---

The `ChainMergeConfig` operation executes multiple chains of operations (branches) with the same input data, then combines their results back into the main chain. Each branch can transform the data differently, and **all outputs from all branches** are passed to the next step.

**Key characteristics:**
- Each branch receives the **same input data**
- Each branch can **transform data differently**
- **All branch outputs are combined** and sent to the next step
- One input item can become **multiple output items**
- Useful for creating multiple views, enriching from multiple sources, or denormalizing data

**Warning:** If branches don't filter or modify items, subsequent steps will receive duplicate data,
as `merge` doesn't handle duplicates automatically. 
This behavior can be leveraged to create multiple versions of an item.

## Configuration

Use `ChainMergeConfig` and add branches with the `addMerge()` method:

```php
use Oliverde8\Component\PhpEtl\OperationConfig\ChainMergeConfig;
use Oliverde8\Component\PhpEtl\ChainConfig;

$mergeConfig = new ChainMergeConfig();
$mergeConfig
    ->addMerge($branch1ChainConfig)
    ->addMerge($branch2ChainConfig)
    ->addMerge($branch3ChainConfig);
```

Each branch is a `ChainConfig` that can contain any sequence of operations.

## Example: Creating Multiple Product Variants

Here's an example that creates both simple and configurable product records from a single input:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainMergeConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;

$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink(
        (new ChainMergeConfig())
            // Branch 1: Create simple product
            ->addMerge(
                (new ChainConfig())
                    ->addLink((new RuleTransformConfig(false))
                        ->addColumn('sku', [['get' => ['field' => 'sku']], ['append' => '-simple']])
                        ->addColumn('type', [['const' => 'simple']])
                        ->addColumn('name', [['get' => ['field' => 'name']]])
                    )
            )
            // Branch 2: Create configurable product
            ->addMerge(
                (new ChainConfig())
                    ->addLink((new RuleTransformConfig(false))
                        ->addColumn('sku', [['get' => ['field' => 'sku']], ['append' => '-configurable']])
                        ->addColumn('type', [['const' => 'configurable']])
                        ->addColumn('name', [['get' => ['field' => 'name']]])
                    )
            )
    )
    // All branch outputs (2 products per input) go to the CSV file
    ->addLink(new CsvFileWriterConfig('merged-products.csv'));

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem(['file' => 'products.csv'])]),
    []
);
```

**Result**: Each input product becomes **2 output rows** - one simple, one configurable.

## Example: Customer Data with Multiple Views

Create different data views for different systems from the same customer data:

```php
$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink(
        (new ChainMergeConfig())
            // Branch 1: Contact information view
            ->addMerge(
                (new ChainConfig())
                    ->addLink((new RuleTransformConfig(false))
                        ->addColumn('customer_id', [['get' => ['field' => 'ID']]])
                        ->addColumn('full_name', [
                            ['implode' => [
                                'values' => [
                                    [['get' => ['field' => 'FirstName']]],
                                    [['get' => ['field' => 'LastName']]],
                                ],
                                'with' => ' ',
                            ]]
                        ])
                        ->addColumn('email', [['get' => ['field' => 'Email']]])
                        ->addColumn('data_type', [['const' => 'contact']])
                    )
            )
            // Branch 2: Subscription status view
            ->addMerge(
                (new ChainConfig())
                    ->addLink((new RuleTransformConfig(false))
                        ->addColumn('customer_id', [['get' => ['field' => 'ID']]])
                        ->addColumn('is_subscribed', [['get' => ['field' => 'IsSubscribed']]])
                        ->addColumn('subscription_date', [['get' => ['field' => 'CreatedAt']]])
                        ->addColumn('data_type', [['const' => 'subscription']])
                    )
            )
    )
    ->addLink(new CsvFileWriterConfig('customer-views.csv'));
```

**Result**: Each customer becomes **2 rows** - one with contact info, one with subscription data.

## Example: Multi-Source API Enrichment

Enrich data by calling multiple APIs and merging all responses:

```php
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\FailSafeConfig;

$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink(
        (new ChainMergeConfig())
            // Branch 1: Get customer profile
            ->addMerge(
                (new ChainConfig())
                    ->addLink(new SimpleHttpConfig(
                        url: '@"https://api.example.com/customers/"~data["customer_id"]',
                        method: 'GET',
                        responseIsJson: true,
                        responseKey: 'profile'
                    ))
                    ->addLink((new RuleTransformConfig(false))
                        ->addColumn('customer_id', [['get' => ['field' => 'customer_id']]])
                        ->addColumn('name', [['get' => ['field' => 'profile', 'name']]])
                        ->addColumn('source', [['const' => 'profile_api']])
                    )
            )
            // Branch 2: Get order history (with error handling)
            ->addMerge(
                new FailSafeConfig(
                    chainConfig: (new ChainConfig())
                        ->addLink(new SimpleHttpConfig(
                            url: '@"https://api.example.com/orders?customer="~data["customer_id"]',
                            method: 'GET',
                            responseIsJson: true,
                            responseKey: 'orders'
                        ))
                        ->addLink((new RuleTransformConfig(false))
                            ->addColumn('customer_id', [['get' => ['field' => 'customer_id']]])
                            ->addColumn('total_orders', [['get' => ['field' => 'orders', 'total']]])
                            ->addColumn('source', [['const' => 'orders_api']])
                        ),
                    exceptionsToCatch: [\Exception::class],
                    nbAttempts: 3
                )
            )
    )
    ->addLink(new CsvFileWriterConfig('customer-enriched.csv'));
```

**Result**: Each customer becomes **2 rows** (or 1 if orders API fails) with data from different APIs.

## Example: Denormalizing Hierarchical Data

Split hierarchical JSON data into flat records:

```php
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\JsonExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SplitItemConfig;

// Input: {"product_id": 123, "name": "T-Shirt", "variants": ["Small", "Medium", "Large"]}

$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new JsonExtractConfig())
    ->addLink(
        (new ChainMergeConfig())
            // Branch 1: Base product info
            ->addMerge(
                (new ChainConfig())
                    ->addLink((new RuleTransformConfig(false))
                        ->addColumn('product_id', [['get' => ['field' => 'product_id']]])
                        ->addColumn('name', [['get' => ['field' => 'name']]])
                        ->addColumn('type', [['const' => 'base']])
                        ->addColumn('variant', [['const' => null]])
                    )
            )
            // Branch 2: Each variant becomes a separate row
            ->addMerge(
                (new ChainConfig())
                    ->addLink(new SplitItemConfig(path: 'variants'))
                    ->addLink((new RuleTransformConfig(false))
                        ->addColumn('product_id', [['get' => ['field' => 'product_id']]])
                        ->addColumn('name', [['get' => ['field' => 'name']]])
                        ->addColumn('type', [['const' => 'variant']])
                        ->addColumn('variant', [['get' => ['field' => 'data']]])
                    )
            )
    )
    ->addLink(new CsvFileWriterConfig('products-flat.csv'));
```

**Result**: 1 base row + 3 variant rows = **4 total rows** per product.

## Common Use Cases

- **Multi-view data export**: Create different data views for different systems
- **Product variants**: Generate simple and configurable products from base data
- **Data enrichment**: Combine information from multiple APIs or databases
- **Customer 360**: Merge profile, orders, support tickets into comprehensive view
