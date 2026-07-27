---
layout: base
title: PHP-ETL - Operations
subTitle: Transform - Rule Engine – Data Transformation
---

The **Rule Engine** is a lightweight transformation component that converts an associative array into another associative array using a flexible set of rules.

It is designed to be used within PHP-ETL through the `RuleTransformConfig`.

## Typed Rules (Recommended)

Each rule has a matching `*RuleConfig` class under `Oliverde8\Component\RuleEngine\RuleConfig`, giving full IDE
autocomplete and constructor validation instead of guessing array shapes:

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

`addColumn()` accepts either a typed `RuleConfigInterface` or the legacy array syntax described below — they can
even be mixed column-by-column in the same `RuleTransformConfig`, since each column's rule is resolved independently.

### Available Typed Rules

| Class | Equivalent to | Description |
|-------|---------------|-------------|
| `GetRuleConfig(string\|array $field)` | `get` | Fetches a value from the input array by key (or multiple keys). |
| `ConstantRuleConfig(mixed $value)` | `constant` | Returns a constant, static value. |
| `ImplodeRuleConfig(array $values, string $with = '')` | `implode` | Concatenates the results of `$values` (each a `RuleConfigInterface`) using `$with` as delimiter. |
| `StrToLowerRuleConfig(RuleConfigInterface $value)` | `str_lower` | Converts the result of `$value` to lowercase. |
| `StrToUpperRuleConfig(RuleConfigInterface $value)` | `str_upper` | Converts the result of `$value` to uppercase. |
| `ExpressionRuleConfig(string $expression, array $values = [])` | `expression_language` | Leverages [Symfony Expression Language](https://symfony.com/doc/current/components/expression_language/syntax.html). `$values` is a map of variable name to a `RuleConfigInterface` resolved before evaluation. |

### Adding Your Own Typed Rule

Implement `Oliverde8\Component\RuleEngine\RuleConfig\RuleConfigInterface` for the value object, and
`Oliverde8\Component\RuleEngine\Rules\ConfigurableRuleInterface` (alongside the existing `RuleInterface`) on the
rule that applies it — `getConfigClass()` tells `RuleApplier` which config class this rule handles:

```php
use Oliverde8\Component\RuleEngine\RuleConfig\RuleConfigInterface;

final class UppercaseFirstRuleConfig implements RuleConfigInterface
{
    public function __construct(public readonly RuleConfigInterface $value) {}
}
```

```php
use Oliverde8\Component\RuleEngine\Rules\AbstractRule;
use Oliverde8\Component\RuleEngine\Rules\ConfigurableRuleInterface;

final class UppercaseFirstRule extends AbstractRule implements ConfigurableRuleInterface
{
    public function apply(array $rowData, array &$transformedData, array $options = [])
    {
        // legacy array-based path, only needed if you also want array syntax support
        return ucfirst((string) $options['value']);
    }

    public function validate(array $options): void
    {
        $this->requireOption('value', $options);
    }

    public function getRuleCode(): string
    {
        return 'uppercase_first';
    }

    public function getConfigClass(): string
    {
        return UppercaseFirstRuleConfig::class;
    }

    public function applyConfig(array $rowData, array &$transformedData, RuleConfigInterface $config): mixed
    {
        assert($config instanceof UppercaseFirstRuleConfig);
        $value = $this->ruleApplier->applyConfig($rowData, $transformedData, $config->value);
        return ucfirst((string) $value);
    }
}
```

Register the rule instance with `RuleApplier` exactly as you would any other rule (see
[Adding Your Own Rules](#adding-your-own-rules) below) — `RuleApplier::registerRule()` automatically indexes it
under `getConfigClass()` as well as `getRuleCode()`, so both `addColumn('field', ['uppercase_first' => [...]])` and
`addColumn('field', new UppercaseFirstRuleConfig(...))` work through the same registered rule.

---

## Array-Based Rules (Deprecated)

The original syntax — plain nested arrays, each keyed by rule name — still works exactly as before, but
`RuleTransformConfig::addColumn()` now triggers a deprecation notice when given an array instead of a
`RuleConfigInterface`. Existing chains keep running unchanged; migrate at your own pace to the typed classes above.

Each rule defines how a specific value in the output array is computed. Rules can be nested and composed for complex transformations.

### Expression Language (`expression_language`)

Leverages the [Symfony Expression Language](https://symfony.com/doc/3.4/components/expression_language/syntax.html) to compute values dynamically.

| Parameter     | Type   | Description |
|---------------|--------|-------------|
| `expression`  | string | The expression to evaluate. Input data is available as `rowData`. |
| `values`      | array  | (Optional) Additional variables made available to the expression. |


### Value Fetcher (`get`)

Fetches a value from the input array by key.

| Parameter | Type   | Description |
|-----------|--------|-------------|
| `field`   | string | The key of the input array to retrieve the value from. |


### Implode (`implode`)

Concatenates multiple values into a single string using a delimiter.

| Parameter | Type   | Description |
|-----------|--------|-------------|
| `value`   | rule   | A rule (or nested rules) that returns an array to implode. |
| `with`    | string | The delimiter used to join the values. |


### String To Lower (`str_lower`)

Converts a string to lowercase.

| Parameter | Type | Description |
|-----------|------|-------------|
| `value`   | rule | A rule that resolves to the string to lowercase. |


### String To Upper (`str_upper`)

Converts a string to uppercase.

| Parameter | Type | Description |
|-----------|------|-------------|
| `value`   | rule | A rule that resolves to the string to uppercase. |


### Constant (`constant`)

Returns a constant, static value.

| Parameter | Type  | Description |
|-----------|-------|-------------|
| `value`   | mixed | The fixed value to be returned. |


## Deprecated Rule

### Condition (`condition`) – *Deprecated*

**Deprecated:** Use `expression_language` instead, which provides more powerful and flexible conditional logic.

A basic conditional evaluator for branching logic.

| Parameter   | Type | Description |
|-------------|------|-------------|
| `if`        | rule | The left-hand value to compare. |
| `value`     | rule | The right-hand value to compare against. |
| `operation` | rule | The comparison operator (`eq`, `neq`, `in`). |
| `then`      | rule | The result if the condition is `true`. |
| `else`      | rule | The result if the condition is `false`. |


## Example Use

Here's an example of how to use rules to transform a CSV row:

```yaml
operation: rule-engine-transformer
options:
  add: false
  columns:
    FullName:
      rules:
        - implode:
            values:
              - [{ get: { field: "FirstName" } }]
              - [{ get: { field: "LastName" } }]
            with: " "
    IsActive:
      rules:
        - expression_language:
            expression: "rowData['IsSubscribed'] == 'yes'"
```

---

## Adding Your Own Rules

While PHP-ETL provides a powerful set of built-in rules, you may encounter situations where you need to implement your own custom logic. You can extend the `RuleApplier` class to add your own rules.

Here's how you can create and use a custom rule:

**1. Create a custom `RuleApplier` class:**

First, create a new class that extends `Oliverde8\Component\RuleEngine\RuleApplier`.

```php
<?php

namespace App\Etl\RuleEngine;

use Oliverde8\Component\RuleEngine\RuleApplier;

class CustomRuleApplier extends RuleApplier
{
    public function apply($data, $rowData, $params)
    {
        // Implement your custom rule logic here.
        return "new value";
    }
}
```

**2. Register your custom `RuleApplier` with the `ChainBuilderV2`:**

When creating your `ChainBuilderV2`, pass your custom `RuleApplier` to the `GenericChainFactory` for `RuleTransformConfig`.

{% capture column1 %}
#### 🐘 Standalone

```php
<?php

use App\Etl\RuleEngine\CustomRuleApplier;
use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\RuleTransformOperation;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\PhpEtl\Builder\Factories\GenericChainFactory;

$customRuleApplier = new CustomRuleApplier();

$chainBuilder = new ChainBuilderV2([
    new GenericChainFactory(
        RuleTransformOperation::class,
        RuleTransformConfig::class,
        injections: ['ruleApplier' => $customRuleApplier]
    ),
    // ... other factories
]);

// Use the custom rule in your chain
$chainConfig = new ChainConfig();
$chainConfig->addLink((new RuleTransformConfig(add: false))
    ->addColumn('MyCustomField', [
        ['myCustomRule' => [
            'field1' => ['get' => ['field' => 'FirstName']],
            'field2' => ['get' => ['field' => 'LastName']],
        ]]
    ])
);

$processor = $chainBuilder->createChain($chainConfig);
```
{% endcapture %}
{% capture column2 %}
#### 🎵 Symfony

```yaml
services:
  App\Etl\RuleEngine\CustomRuleApplier:
    class: App\Etl\RuleEngine\CustomRuleApplier
    autowire: true
    tags:
      - { name: etl.rule }

  # The GenericChainFactory will automatically use your custom RuleApplier
  # when it's injected via dependency injection
```
{% endcapture %}
{% include block/2column.html column1=column1 column2=column2 %}

**3. Use your custom rule in your chain configuration:**

Once you have configured your `ChainBuilderV2` to use your custom `RuleApplier`, you can use your custom rule in your chain.

```php
$chainConfig = new ChainConfig();
$chainConfig->addLink((new RuleTransformConfig(add: false))
    ->addColumn('MyCustomField', [
        ['myCustomRule' => [
            'field1' => ['get' => ['field' => 'FirstName']],
            'field2' => ['get' => ['field' => 'LastName']],
        ]]
    ])
);
```
