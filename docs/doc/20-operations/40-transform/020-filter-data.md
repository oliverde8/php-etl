---
layout: base
title: PHP-ETL - Operations
subTitle: Transform - Filter Data
---

The **Filter Data** operation selectively skips items in the chain based on rules. It uses the [rule engine](030-rule-transformer.html) to evaluate conditions; if the condition is not met, the item is not passed to subsequent operations.

---

## Purpose

Use `FilterDataConfig` to:
- Remove unwanted items from the data stream
- Keep only items that meet specific criteria
- Filter based on field values or Symfony expressions
- Implement conditional data processing logic

---

## Configuration

### Basic Configuration

```php
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;

// Keep items where field value is truthy
$config = new FilterDataConfig([
    ["get" => ["field" => "IsSubscribed"]]
]);
```

### Constructor Parameters

```php
public function __construct(
    public readonly array $rules,
    public readonly bool $negate = false,
    string $flavor = 'default'
)
```

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `rules` | `array` | *required* | Rule engine rules to evaluate. Item kept if rule evaluates to truthy value |
| `negate` | `bool` | `false` | If `true`, inverts the logic (keeps items that evaluate to falsy) |
| `flavor` | `string` | `'default'` | Operation flavor for custom implementations |

---

## Rule Engine Basics

The `rules` parameter uses the rule engine syntax. The rule engine has the following operations:

### Get Field Value
```php
// Keep items where field exists and is truthy
["get" => ["field" => "fieldName"]]
```

### Expression Language (for complex conditions)
```php
// Keep items where status equals "published" using Symfony Expression Language
["expression_language" => [
    "expression" => "rowData.status == 'published'"
]]

// Keep items where age >= 18
["expression_language" => [
    "expression" => "rowData.age >= 18"
]]

// Complex condition: status is published AND age >= 18
["expression_language" => [
    "expression" => "rowData.status == 'published' and rowData.age >= 18"
]]
```

### Constant Value
```php
// Always keep all items (returns true constant)
["constant" => ["value" => true]]

// Never keep items (returns false constant)
["constant" => ["value" => false]]
```

For all available rule engine operations, see the [Rule Transformer documentation](030-rule-transformer.html).

---

## Input/Output

- **Input**: `DataItem` objects with data to filter
- **Output**: `DataItem` objects that match the filter criteria (others are discarded)

---

## Examples

### Example 1: Simple Field Filter

Keep only subscribed customers:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink(new FilterDataConfig([
        ["get" => ["field" => "IsSubscribed"]]
    ]))
    ->addLink(new CsvFileWriterConfig('subscribed_customers.csv'));

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem(['file' => 'data/customers.csv'])]),
    []
);
```

**Result**: Only rows where `IsSubscribed` is truthy (non-null, non-false) are written to output.

### Example 2: Negated Filter

Keep only non-subscribed customers using negate:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink(new FilterDataConfig(
        rules: [["get" => ["field" => "IsSubscribed"]]],
        negate: true
    ))
    ->addLink(new CsvFileWriterConfig('not_subscribed_customers.csv'));

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem(['file' => 'data/customers.csv'])]),
    []
);
```

**Result**: Only rows where `IsSubscribed` is falsy (null or false) are written to output.

### Example 3: Equality Check with Expression Language

Keep only published articles:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\JsonExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new JsonExtractConfig())
    ->addLink(new FilterDataConfig([
        ["expression_language" => [
            "expression" => "rowData.status == 'published'"
        ]]
    ]))
    ->addLink(new CsvFileWriterConfig('published_articles.csv'));

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem('articles.json')]),
    []
);
```

### Example 4: Numeric Range Filter

Keep only adults (age >= 18):

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink(new FilterDataConfig([
        ["expression_language" => [
            "expression" => "rowData.age >= 18"
        ]]
    ]))
    ->addLink(new CsvFileWriterConfig('adults.csv'));

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem('users.csv')]),
    []
);
```

### Example 5: Multiple Conditions (AND)

Keep only active, verified users:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink(new FilterDataConfig([
        ["expression_language" => [
            "expression" => "rowData.status == 'active' and rowData.verified == true"
        ]]
    ]))
    ->addLink(new CsvFileWriterConfig('active_verified_users.csv'));

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem('users.csv')]),
    []
);
```

### Example 6: Multiple Conditions (OR)

Keep orders that are either pending or processing:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink(new FilterDataConfig([
        ["expression_language" => [
            "expression" => "rowData.status in ['pending', 'processing']"
        ]]
    ]))
    ->addLink(new CsvFileWriterConfig('active_orders.csv'));

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem('orders.csv')]),
    []
);
```

### Example 7: Pattern Matching with Expression Language

Keep emails from specific domains:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink(new FilterDataConfig([
        ["expression_language" => [
            "expression" => "rowData.email matches '/@(company|partner)\\.com$/'"
        ]]
    ]))
    ->addLink(new CsvFileWriterConfig('business_contacts.csv'));

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem('contacts.csv')]),
    []
);
```

### Example 8: Split into Multiple Files

Use `ChainSplitConfig` with filters to split data into multiple output files:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Building\ChainSplitConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

// Chain for subscribed customers
$subscribedChain = new ChainConfig();
$subscribedChain
    ->addLink(new FilterDataConfig([
        ["get" => ["field" => "IsSubscribed"]]
    ]))
    ->addLink(new CsvFileWriterConfig('customers-subscribed.csv'));

// Chain for non-subscribed customers
$notSubscribedChain = new ChainConfig();
$notSubscribedChain
    ->addLink(new FilterDataConfig(
        rules: [["get" => ["field" => "IsSubscribed"]]],
        negate: true
    ))
    ->addLink(new CsvFileWriterConfig('customers-not-subscribed.csv'));

// Main chain
$mainChain = new ChainConfig();
$mainChain
    ->addLink(new CsvExtractConfig())
    ->addLink(new ChainSplitConfig()
        ->addSplit($subscribedChain)
        ->addSplit($notSubscribedChain)
    )
    ->addLink(new CsvFileWriterConfig('customers-all.csv'));

$chainProcessor = $chainBuilder->createChain($mainChain);
$chainProcessor->process(
    new ArrayIterator([new DataItem(['file' => 'data/customers.csv'])]),
    []
);
```

**Result**: Three files are created:
- `customers-subscribed.csv` - Only subscribed customers
- `customers-not-subscribed.csv` - Only non-subscribed customers  
- `customers-all.csv` - All customers

### Example 9: Date Range Filter

Keep records from the last 30 days:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink(new FilterDataConfig([
        ["expression_language" => [
            "expression" => "rowData.created_at >= date('-30 days')",
            "values" => []
        ]]
    ]))
    ->addLink(new CsvFileWriterConfig('recent_orders.csv'));

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem('orders.csv')]),
    []
);
```

### Example 10: Null/Empty Check

Keep only records with non-empty email addresses:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink(new FilterDataConfig([
        ["expression_language" => [
            "expression" => "rowData.email is not null and rowData.email != ''"
        ]]
    ]))
    ->addLink(new CsvFileWriterConfig('contacts_with_email.csv'));

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem('contacts.csv')]),
    []
);
```

---

## Available Rule Engine Operations
For complex conditions (comparisons, AND/OR logic, etc.), use `expression_language` with [Symfony Expression Language syntax](https://symfony.com/doc/current/components/expression_language/syntax.html).
