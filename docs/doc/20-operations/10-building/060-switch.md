---
layout: base
title: PHP-ETL - Operations
subTitle: Building Blocks - Switch
---

The `SwitchConfig` operation evaluates several conditions against the item's data, in the order they were added,
and routes the item to the branch of the **first matching case**. If none match, it falls back to an optional
`default` branch, or lets the item continue unchanged. It's the N-way generalization of [`If`](050-if.html):
`If` gives you exactly two outcomes (`then`/`else`), `Switch` gives you as many as you need without nesting
`If` inside `If`.

**Key characteristics:**
- Cases are evaluated **in order**; the first one whose condition matches wins
- Routes the item to **exactly one** branch — never more than one
- The chosen branch can **freely modify** the item, since no other branch runs alongside it
- `default` is optional — without it, no case matching just lets the item continue unchanged
- Each case's condition is either Rule Engine rules or a Symfony Expression Language string (same options as `FilterDataConfig`/`If`)

## Configuration

Build cases fluently with `addCase()`, mirroring `addSplit()`/`addMerge()`/`addLink()`:

```php
use Oliverde8\Component\PhpEtl\OperationConfig\SwitchConfig;
use Oliverde8\Component\PhpEtl\ChainConfig;

$switchConfig = (new SwitchConfig(default: $defaultChainConfig)) // default is optional, like If's `else`
    ->addCase($usChainConfig, expression: 'data["country"] == "US"')
    ->addCase($frChainConfig, expression: 'data["country"] == "FR"');

$chainConfig->addLink($switchConfig);
```

Each case can use Rule Engine `rules` instead of `expression` — the two are mutually exclusive per case, exactly
like `FilterDataConfig`/`IfConfig`:

```php
$switchConfig->addCase($chainConfig, rules: [['get' => ['field' => 'IsPremium']]]);
```

**Parameters:**
- `default`: An optional `ChainConfig` run when no case matches. Without it, the item just continues to the next step unchanged
- `isolateContext`: When `true`, the chosen branch runs against its own clone of the execution context instead of the parent's. Default `false`

`addCase(ChainConfig $then, array $rules = [], ?string $expression = null)`:
- `$then`: A `ChainConfig` run when this case's condition matches
- `$rules`: Rule Engine rules evaluated against the item's data. Mutually exclusive with `$expression`
- `$expression`: A Symfony Expression Language condition, evaluated against `data`/`context`. Alternative to `$rules` for simple boolean conditions

{% include block/isolate-context-branch.md operation="switch" var="switchConfig" config="SwitchConfig" %}

## Example: Routing Orders by Country

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\SwitchConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink((new SwitchConfig(
        default: (new ChainConfig())
            ->addLink((new RuleTransformConfig(false))
                ->addColumn('region', [['constant' => ['value' => 'international']]])
            )
    ))
        ->addCase(
            (new ChainConfig())
                ->addLink((new RuleTransformConfig(false))
                    ->addColumn('region', [['constant' => ['value' => 'north-america']]])
                ),
            expression: 'data["country"] in ["US", "CA", "MX"]'
        )
        ->addCase(
            (new ChainConfig())
                ->addLink((new RuleTransformConfig(false))
                    ->addColumn('region', [['constant' => ['value' => 'europe']]])
                ),
            expression: 'data["country"] in ["FR", "DE", "ES"]'
        ))
    ->addLink(new CsvFileWriterConfig('orders-tagged.csv'));
```

**Result**: Every row continues to `orders-tagged.csv`, tagged with a `region` based on the first matching case —
`north-america`, `europe`, or `international` if nothing matched, all in one output stream.

## Common Use Cases

- **Multi-way routing**: Tag or transform items differently depending on which of several conditions matches
- **Replacing nested `If`**: Flatten what would otherwise be `If` nested inside `If`'s `else`, three or more cases deep
- **Rule-based dispatch**: Route items to per-category processing logic (by country, status, tier, type, etc.)
