---
layout: base
title: PHP-ETL - Operations
subTitle: Transform - Rule Transformer
---

The **Rule Transformer** operation builds a new associative array from an item's data, one column at a time,
by applying a [Rule Engine](/doc/15-rule-engine/010-rule-engine.html) rule to compute each column's value.

---

## Purpose

Use `RuleTransformConfig` to:
- Reshape an item's data into a new structure (rename, combine, or drop fields)
- Compute derived columns (concatenation, case conversion, conditional values, ...)
- Either replace the item's data entirely, or add computed columns alongside the existing data

---

## Configuration

```php
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\RuleEngine\RuleConfig\ExpressionRuleConfig;
use Oliverde8\Component\RuleEngine\RuleConfig\GetRuleConfig;
use Oliverde8\Component\RuleEngine\RuleConfig\ImplodeRuleConfig;

$config = (new RuleTransformConfig(add: false))
    ->addColumn('FullName', new ImplodeRuleConfig(
        values: [new GetRuleConfig('FirstName'), new GetRuleConfig('LastName')],
        with: ' ',
    ))
    ->addColumn('IsActive', new ExpressionRuleConfig(
        "rowData['IsSubscribed'] == 'yes'"
    ));
```

**Parameters:**
- `add` (bool, default `true`): when `true`, computed columns are merged into the item's existing data; when
  `false`, the item's data is replaced entirely by the computed columns
- `addColumn(string $columnName, RuleConfigInterface|array $rules)`: registers one output column, computed by
  `$rules`. Call it once per column, in any order

`addColumn()` accepts either a typed `RuleConfigInterface` (see the [Rule Engine](/doc/15-rule-engine/010-rule-engine.html)
page for the full catalog and [Custom Rules](/doc/15-rule-engine/020-custom-rules.html) for writing your own) or
the legacy array-based rule syntax, which is deprecated — pass a `RuleConfigInterface`
(e.g. `new GetRuleConfig(...)`) instead.

---

## Example: Tagging Subscribed Customers

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\RuleEngine\RuleConfig\ExpressionRuleConfig;
use Oliverde8\Component\RuleEngine\RuleConfig\GetRuleConfig;
use Oliverde8\Component\RuleEngine\RuleConfig\ImplodeRuleConfig;

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink((new RuleTransformConfig(add: true))
        ->addColumn('FullName', new ImplodeRuleConfig(
            values: [new GetRuleConfig('FirstName'), new GetRuleConfig('LastName')],
            with: ' ',
        ))
        ->addColumn('IsActive', new ExpressionRuleConfig(
            "rowData['IsSubscribed'] == 'yes'"
        )))
    ->addLink(new CsvFileWriterConfig('customers-tagged.csv'));
```

**Result**: Every row keeps its original columns (`add: true`) plus two computed ones: `FullName` (first and
last name joined by a space) and `IsActive` (a boolean derived from `IsSubscribed`).
