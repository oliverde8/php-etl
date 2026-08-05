---
layout: base
title: PHP-ETL - Rule Engine
subTitle: Rule Engine - Migrating to Typed Rules
width: large
---

## Migrating from Array Syntax to Typed Rules

Every array-based rule maps 1:1 to a `*RuleConfig` class — same parameters, just a constructor instead of a
keyed array. No behavior changes, so rules can be migrated one at a time, even mixed within the same
`RuleTransformConfig` column-by-column.

| Array syntax | Typed class |
|---|---|
| `get: { field: ... }` | `GetRuleConfig($field)` |
| `constant: { value: ... }` | `ConstantRuleConfig($value)` |
| `implode: { values: ..., with: ... }` | `ImplodeRuleConfig($values, $with)` |
| `str_lower: { value: ... }` | `StrToLowerRuleConfig($value)` |
| `str_upper: { value: ... }` | `StrToUpperRuleConfig($value)` |
| `expression_language: { expression: ..., values: ... }` | `ExpressionRuleConfig($expression, $values)` |

### Before

```yaml
columns:
  FullName:
    rules:
      - implode:
          values:
            - [{ get: { field: "FirstName" } }]
            - [{ get: { field: "LastName" } }]
          with: " "
```

### After

```php
use Oliverde8\Component\RuleEngine\RuleConfig\GetRuleConfig;
use Oliverde8\Component\RuleEngine\RuleConfig\ImplodeRuleConfig;

$config->addColumn('FullName', new ImplodeRuleConfig(
    values: [new GetRuleConfig('FirstName'), new GetRuleConfig('LastName')],
    with: ' ',
));
```

Custom rule types you registered yourself follow the same pattern — see [Custom Rules](/doc/15-rule-engine/020-custom-rules.html)
for building the typed equivalent of a hand-rolled array rule.
