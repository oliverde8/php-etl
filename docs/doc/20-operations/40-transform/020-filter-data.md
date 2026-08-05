---
layout: base
title: PHP-ETL - Operations
subTitle: Transform - Filter Data
---

The **Filter Data** operation selectively skips items in the chain based on rules. It uses the [Rule Engine](/doc/15-rule-engine/010-rule-engine.html) to evaluate conditions; if the condition is not met, the item is not passed to subsequent operations.

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
use Oliverde8\Component\RuleEngine\RuleConfig\GetRuleConfig;

// Keep items where field value is truthy
$config = new FilterDataConfig(new GetRuleConfig('IsSubscribed'));
```

### Expression-Based Configuration

For a simple boolean condition, an `Expression` is often less verbose than a typed rule — a [Symfony Expression Language](https://symfony.com/doc/current/components/expression_language.html) string evaluated against `data` (the item's data) and `context` (the execution context's parameters):

```php
use Oliverde8\Component\PhpEtl\Expression\Expression;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;

$config = new FilterDataConfig(new Expression('data["IsSubscribed"] == true'));
```

### Constructor Parameters

```php
public function __construct(
    public readonly RuleConfigInterface|Expression|array $rules = [],
    public readonly bool $negate = false,
    string $flavor = 'default'
)
```

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `rules` | `RuleConfigInterface\|Expression\|array` | `[]` | A typed rule or an `Expression`. Item kept if the result is truthy. A plain array is also accepted but deprecated, see [Legacy Array Syntax](/doc/15-rule-engine/030-legacy-array-syntax.html) |
| `negate` | `bool` | `false` | If `true`, inverts the logic (keeps items that evaluate to falsy) |
| `flavor` | `string` | `'default'` | Operation flavor for custom implementations |

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
use Oliverde8\Component\RuleEngine\RuleConfig\GetRuleConfig;

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink(new FilterDataConfig(new GetRuleConfig('IsSubscribed')))
    ->addLink(new CsvFileWriterConfig('subscribed_customers.csv'));

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem(['file' => 'data/customers.csv'])]),
    []
);
```

**Result**: Only rows where `IsSubscribed` is truthy (non-null, non-false) are written to output.

### Example 2: Negated Filter

Keep only non-subscribed customers using `negate`:

```php
->addLink(new FilterDataConfig(
    rules: new GetRuleConfig('IsSubscribed'),
    negate: true
))
```

**Result**: Only rows where `IsSubscribed` is falsy (null or false) are written to output.

### Example 3: Conditions with Expressions

For anything beyond a single truthy field, an `Expression` reads naturally and covers comparisons, boolean
logic, membership checks, and regex matching in one line:

```php
use Oliverde8\Component\PhpEtl\Expression\Expression;

// Equality
new FilterDataConfig(new Expression('data["status"] == "published"'));

// Numeric range
new FilterDataConfig(new Expression('data["age"] >= 18'));

// AND
new FilterDataConfig(new Expression('data["status"] == "active" and data["verified"] == true'));

// OR / membership
new FilterDataConfig(new Expression('data["status"] in ["pending", "processing"]'));

// Pattern matching
new FilterDataConfig(new Expression('data["email"] matches "/@(company|partner)\\.com$/"'));

// Reading from the execution context alongside the item's data
new FilterDataConfig(new Expression('data["country"] == context["allowedCountry"]'));
```

See the [Symfony Expression Language syntax](https://symfony.com/doc/current/components/expression_language/syntax.html) reference for everything expressions support.

### Example 4: Split into Multiple Files

Use `ChainSplitConfig` with filters to split data into multiple output files:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainSplitConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\RuleEngine\RuleConfig\GetRuleConfig;

// Chain for subscribed customers
$subscribedChain = new ChainConfig();
$subscribedChain
    ->addLink(new FilterDataConfig(new GetRuleConfig('IsSubscribed')))
    ->addLink(new CsvFileWriterConfig('customers-subscribed.csv'));

// Chain for non-subscribed customers
$notSubscribedChain = new ChainConfig();
$notSubscribedChain
    ->addLink(new FilterDataConfig(
        rules: new GetRuleConfig('IsSubscribed'),
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
