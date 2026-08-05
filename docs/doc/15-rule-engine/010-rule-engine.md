---
layout: base
title: PHP-ETL - Rule Engine
subTitle: Rule Engine - Transform data with typed rules
width: large
---

## Rule Engine

The **Rule Engine** (`Oliverde8\Component\RuleEngine`) is a standalone component that computes a value by
applying a rule against an associative array. It's not tied to PHP-ETL's chain paradigm — it can be used on its
own — but within PHP-ETL it powers several operations:

- [Rule Transformer](/doc/20-operations/40-transform/030-rule-transformer.html) — build each output column from a rule
- [Filter Data](/doc/20-operations/40-transform/020-filter-data.html) — keep or discard an item based on a rule's result
- [If](/doc/20-operations/10-building/050-if.html) — route an item to a branch based on a rule's result
- [Switch](/doc/20-operations/10-building/060-switch.html) — route an item to the first matching case's branch

---

## From a Tree to a Flat Result

Source data is often a tree — nested arrays, several levels deep, as you'd get parsing JSON or a complex API
response. Rules read that tree and combine it into whatever shape you need, typically a flat associative array.

`GetRuleConfig` walks down one level per array entry, so `['customer', 'address', 'city']` reads three levels
deep:

```php
$data = [
    'customer' => [
        'name' => ['first' => 'Jane', 'last' => 'Doe'],
        'address' => ['city' => 'Paris'],
    ],
];

new GetRuleConfig(['customer', 'name', 'first']); // 'Jane'
new GetRuleConfig(['customer', 'address', 'city']); // 'Paris'
```

Rules compose: a rule's parameters can themselves be rules, so you can combine several tree branches into one
value. `RuleTransformConfig::addColumn()` calls this once per output column, so however deep each read went, the
result is always a flat associative array — one key per column:

```php
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\RuleEngine\RuleConfig\GetRuleConfig;
use Oliverde8\Component\RuleEngine\RuleConfig\ImplodeRuleConfig;

$config = (new RuleTransformConfig(add: false))
    ->addColumn('FullName', new ImplodeRuleConfig(
        values: [
            new GetRuleConfig(['customer', 'name', 'first']),
            new GetRuleConfig(['customer', 'name', 'last']),
        ],
        with: ' ',
    ))
    ->addColumn('City', new GetRuleConfig(['customer', 'address', 'city']));

// Result: ['FullName' => 'Jane Doe', 'City' => 'Paris']
```

---

## Building a Nested Result

`addColumn()` can also do the opposite: build a nested result instead of a flat one. Put a `/` in the column
name and each segment becomes one level of the output array — independently of how the value was read:

```php
$data = ['first_name' => 'Jane', 'last_name' => 'Doe', 'town' => 'Paris'];

$config = (new RuleTransformConfig(add: false))
    ->addColumn('customer/name/first', new GetRuleConfig('first_name'))
    ->addColumn('customer/name/last', new GetRuleConfig('last_name'))
    ->addColumn('customer/city', new GetRuleConfig('town'));

// Result: ['customer' => ['name' => ['first' => 'Jane', 'last' => 'Doe'], 'city' => 'Paris']]
```

---

## Dynamic Columns from Context

A column name can also contain a `{...}` placeholder, resolved against the context parameters passed to
`$chainProcessor->process($items, $parameters)`. When the resolved value is an array, one column is generated
per value instead of a single one — useful when the number of columns depends on something you only know at
runtime, like a list of locales:

```php
$config = (new RuleTransformConfig(add: false))
    ->addColumn('name-{@context/locales}', new GetRuleConfig(['name', '@context/locales']));

$chainProcessor->process($items, ['locales' => ['fr_FR', 'en_US']]);

// Given ['name' => ['fr_FR' => 'Mon Produit', 'en_US' => 'My Product']]:
// Result: ['name-fr_FR' => 'Mon Produit', 'name-en_US' => 'My Product']
```

`@context/locales` in `field` isn't a literal key — for each generated column it resolves to the locale
currently being expanded, so the rule reads the matching translation each time. Use `get` for this rather than
`ExpressionRuleConfig`: `get` returns an empty value for a missing locale, `ExpressionRuleConfig` would throw.

You can combine several placeholders (`'{@context/scope}-{@context/locale}'` generates one column per
combination) and mix this with a nested column name (`'translations/{@context/locales}'`). See
[Flattening Complex Data](/doc/10-examples/140-complex-data.html) for a full worked example, reading a JSON
file and writing the result to CSV.

---

## Typed Rules

Each rule has a matching `*RuleConfig` class under `Oliverde8\Component\RuleEngine\RuleConfig`, giving full IDE
autocomplete and constructor validation instead of guessing array shapes. Wherever an operation accepts a rule
(`RuleTransformConfig::addColumn()`, `FilterDataConfig`/`IfConfig`/`SwitchConfig`'s `rules`), it accepts a typed
`RuleConfigInterface` — they can even be mixed with the legacy array syntax column-by-column in the same
`RuleTransformConfig`, since each column's rule is resolved independently.

### Available Typed Rules

| Class | Description |
|-------|-------------|
| `GetRuleConfig(string\|array $field)` | Fetches a value from the input array by key, or walks a nested path when `$field` is an array. |
| `ConstantRuleConfig(mixed $value)` | Returns a constant, static value. |
| `ImplodeRuleConfig(array $values, string $with = '')` | Concatenates the results of `$values` (each a `RuleConfigInterface`) using `$with` as delimiter. |
| `StrToLowerRuleConfig(RuleConfigInterface $value)` | Converts the result of `$value` to lowercase. |
| `StrToUpperRuleConfig(RuleConfigInterface $value)` | Converts the result of `$value` to uppercase. |
| `ExpressionRuleConfig(string $expression, array $values = [])` | Leverages [Symfony Expression Language](https://symfony.com/doc/current/components/expression_language/syntax.html). `$values` is a map of variable name to a `RuleConfigInterface` resolved before evaluation. |

Need custom logic beyond these six? See [Custom Rules](/doc/15-rule-engine/020-custom-rules.html).

A legacy array-based syntax also still works everywhere a rule is accepted, but it's deprecated — see
[Migrating to Typed Rules](/doc/15-rule-engine/025-migrating-to-typed-rules.html) to convert existing chains, or
[Legacy Array Syntax](/doc/15-rule-engine/030-legacy-array-syntax.html) for reference.
