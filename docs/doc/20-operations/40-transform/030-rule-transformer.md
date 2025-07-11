---
layout: base
title: PHP-ETL - Operations
subTitle: Transform - Rule Engine – Data Transformation
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

🚧 TODO 🚧