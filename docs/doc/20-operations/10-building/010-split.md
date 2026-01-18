---
layout: base
title: PHP-ETL - Operations
subTitle: Building Blocks - Chain Split
---

The `ChainSplitConfig` operation executes multiple, independent chains of operations with the same input data. 
Each chain, or "branch," processes data in parallel without affecting other branches or the main chain. 
This is useful for performing distinct tasks simultaneously, such as logging, sending to an API, and saving to a database.

**Key characteristics:**
- Each branch receives the **same input data**
- Branches execute **independently** - one branch's modifications don't affect others
- The **original item** continues to the next step in the main chain
- Useful for parallel processing, routing data to multiple destinations, or creating filtered outputs

## Configuration

Use `ChainSplitConfig` and add branches with the `addSplit()` method:

```php
use Oliverde8\Component\PhpEtl\OperationConfig\ChainSplitConfig;
use Oliverde8\Component\PhpEtl\ChainConfig;

$splitConfig = new ChainSplitConfig();
$splitConfig
    ->addSplit($branch1ChainConfig)
    ->addSplit($branch2ChainConfig)
    ->addSplit($branch3ChainConfig);
```

Each branch is a `ChainConfig` that can contain any sequence of operations.

## Example: Splitting Data into Multiple Files

Here's an example that reads a CSV file and splits data into different files based on subscription status:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainSplitConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;

$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink(
        (new ChainSplitConfig())
            // Branch 1: Subscribed customers only
            ->addSplit(
                (new ChainConfig())
                    ->addLink(new FilterDataConfig('@data["IsSubscribed"]'))
                    ->addLink(new CsvFileWriterConfig('customers-subscribed.csv'))
            )
            // Branch 2: Non-subscribed customers only
            ->addSplit(
                (new ChainConfig())
                    ->addLink(new FilterDataConfig('@!data["IsSubscribed"]'))
                    ->addLink(new CsvFileWriterConfig('customers-not-subscribed.csv'))
            )
    )
    // After the split, all data continues to the main chain
    ->addLink(new CsvFileWriterConfig('customers-all.csv'));

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem(['file' => 'data/customers.csv'])]),
    []
);
```

**Result**: 
- `customers-subscribed.csv` contains only subscribed customers
- `customers-not-subscribed.csv` contains only non-subscribed customers  
- `customers-all.csv` contains all customers (unfiltered)

## Example: Logging and Processing Simultaneously

Split is useful for logging or monitoring without affecting the main processing flow:

```php
$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink(
        (new ChainSplitConfig())
            // Branch 1: Main processing
            ->addSplit(
                (new ChainConfig())
                    ->addLink((new RuleTransformConfig(false))
                        ->addColumn('id', [['get' => ['field' => 'ID']]])
                        ->addColumn('name', [['get' => ['field' => 'Name']]])
                    )
                    ->addLink(new CsvFileWriterConfig('output/processed.csv'))
            )
            // Branch 2: Logging only
            ->addSplit(
                (new ChainConfig())
                    ->addLink(new LogConfig(
                        message: 'Processing customer ID: @data["ID"]',
                        level: 'info'
                    ))
            )
            // Branch 3: Send to API
            ->addSplit(
                (new ChainConfig())
                    ->addLink(new SimpleHttpConfig(
                        url: 'https://api.example.com/track',
                        method: 'POST',
                        responseIsJson: false
                    ))
            )
    );
```

## Example: Multi-Destination Export

Send the same data to different systems with different transformations:

```php
$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink(
        (new ChainSplitConfig())
            // Branch 1: Export to CRM (full data)
            ->addSplit(
                (new ChainConfig())
                    ->addLink((new RuleTransformConfig(false))
                        ->addColumn('customer_id', [['get' => ['field' => 'ID']]])
                        ->addColumn('email', [['get' => ['field' => 'Email']]])
                        ->addColumn('name', [['get' => ['field' => 'Name']]])
                        ->addColumn('phone', [['get' => ['field' => 'Phone']]])
                    )
                    ->addLink(new CsvFileWriterConfig('export/crm-full.csv'))
            )
            // Branch 2: Export to Marketing (email only)
            ->addSplit(
                (new ChainConfig())
                    ->addLink(new FilterDataConfig('@data["IsSubscribed"]'))
                    ->addLink((new RuleTransformConfig(false))
                        ->addColumn('email', [['get' => ['field' => 'Email']]])
                        ->addColumn('first_name', [['get' => ['field' => 'FirstName']]])
                    )
                    ->addLink(new CsvFileWriterConfig('export/marketing-emails.csv'))
            )
            // Branch 3: Export to Analytics (aggregated)
            ->addSplit(
                (new ChainConfig())
                    ->addLink((new RuleTransformConfig(false))
                        ->addColumn('country', [['get' => ['field' => 'Country']]])
                        ->addColumn('subscription_date', [['get' => ['field' => 'CreatedAt']]])
                    )
                    ->addLink(new CsvFileWriterConfig('export/analytics-summary.csv'))
            )
    );
```

## Common Use Cases

- **Data routing**: Send different subsets to different destinations
- **Parallel exports**: Export same data to multiple formats/systems
- **Multi-format output**: Generate CSV, JSON, and XML from same data
