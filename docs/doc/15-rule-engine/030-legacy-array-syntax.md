---
layout: base
title: PHP-ETL - Rule Engine
subTitle: Rule Engine - Legacy Array Syntax
width: large
---

## Legacy Array Syntax

> If you're starting a new chain, you don't need this page — use [Typed Rules](/doc/15-rule-engine/010-rule-engine.html)
> instead. This is reference material for chains still using the original array-based syntax. Migrating an
> existing chain? See [Migrating to Typed Rules](/doc/15-rule-engine/025-migrating-to-typed-rules.html).

The original syntax — plain nested arrays, each keyed by rule name — still works exactly as before, but every
place that accepts rules now triggers a deprecation notice when given an array instead of a `RuleConfigInterface`.
Existing chains keep running unchanged; migrate at your own pace to the typed classes.

Each rule below defines how a specific value is computed. Rules can be nested and composed for complex
transformations.

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

---

## Example

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
