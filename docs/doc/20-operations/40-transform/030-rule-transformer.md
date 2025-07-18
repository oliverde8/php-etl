---
layout: base
title: PHP-ETL - Operations
subTitle: Transform - Rule Engine – Data Transformation(rule-engine-transformer)
---

The Rule Engine is a lightweight transformation component that converts an associative array into another associative array using a flexible set of rules.

It is designed to be used within PHP-ETL through the `RuleTransformOperation`.


## Available Rules

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

Here’s an example of how to use rules to transform a CSV row:

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

## Adding your own rules

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

**2. Use your custom `RuleApplier` in the `ChainProcessor`:**

When you create your `ChainProcessor`, you need to tell it to use your custom `RuleApplier`.

{% capture column1 %}
#### 🐘 Standalone
```php
<?php

use App\Etl\RuleEngine\CustomRuleApplier;
use Oliverde8\Component\PhpEtl\ChainBuilder;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\RuleTransformOperation;
use Oliverde8\Component\PhpEtl\ChainProcessor;

$customRuleApplier = new CustomRuleApplier();

$chainBuilder = new ChainBuilder();
$chainBuilder->add(
    new RuleTransformOperation(
        $customRuleApplier,
        [
            'my_custom_field' => [
                'rules' => [
                    'myCustomRule' => [
                        'field1' => ['get' => ['field' => 'FirstName']],
                        'field2' => ['get' => ['field' => 'LastName']],
                    ],
                ],
            ],
        ],
        false
    )
);

$processor = new ChainProcessor($chainBuilder);
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
```
{% endcapture %}
{% include block/2column.html column1=column1 column2=column2 %}

**3. Use your custom rule in your YAML configuration:**

Once you have configured your `ChainProcessor` to use your custom `RuleApplier`, you can use your custom rule in your YAML files.

```yaml
operation: rule-engine-transformer
options:
  columns:
    MyCustomField:
      rules:
        - myCustomRule:
            field1: { get: { field: "FirstName" } }
            field2: { get: { field: "LastName" } }
```