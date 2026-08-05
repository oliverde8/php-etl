---
layout: base
title: PHP-ETL - Rule Engine
subTitle: Rule Engine - Custom Rules
width: large
---

## Adding a Custom Rule

The six [built-in rules](/doc/15-rule-engine/010-rule-engine.html#available-typed-rules) cover most cases, but
you can register your own. A rule has two parts: a `RuleConfigInterface` value object (the typed arguments) and
a `RuleInterface` implementation (the logic).

**1. Define the config:**

```php
use Oliverde8\Component\RuleEngine\RuleConfig\RuleConfigInterface;

final class UppercaseFirstRuleConfig implements RuleConfigInterface
{
    public function __construct(public readonly RuleConfigInterface $value) {}
}
```

**2. Implement the rule.** Extend `AbstractRule` and implement `ConfigurableRuleInterface` — `getConfigClass()`
tells `RuleApplier` which config class this rule handles:

```php
use Oliverde8\Component\RuleEngine\Rules\AbstractRule;
use Oliverde8\Component\RuleEngine\Rules\ConfigurableRuleInterface;
use Oliverde8\Component\RuleEngine\RuleConfig\RuleConfigInterface;

final class UppercaseFirstRule extends AbstractRule implements ConfigurableRuleInterface
{
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

    // Only needed if you also want the deprecated array syntax to support this rule.
    public function apply(array $rowData, array &$transformedData, array $options = [])
    {
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
}
```

**3. Register it on a `RuleApplier` and inject that instance** wherever `RuleTransformConfig`, `FilterDataConfig`,
`IfConfig`, or `SwitchConfig` are used — they all take a `RuleApplier` as a constructor dependency, resolved
through `GenericChainFactory`'s `injections`:

{% capture column1 %}
#### 🐘 Standalone

```php
use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\RuleTransformOperation;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\PhpEtl\GenericChainFactory;
use Oliverde8\Component\RuleEngine\RuleApplier;
use Oliverde8\Component\RuleEngine\Rules\Get;
// ... plus any other built-in rules you need (Implode, StrToLower, ...)

$ruleApplier = new RuleApplier($logger, [
    new Get($logger),
    new UppercaseFirstRule(),
]);

$chainBuilder = new ChainBuilderV2($executionContextFactory, [
    new GenericChainFactory(
        RuleTransformOperation::class,
        RuleTransformConfig::class,
        injections: ['ruleApplier' => $ruleApplier],
    ),
    // ... other factories, passing the same $ruleApplier to FilterData/If/Switch as needed
]);
```
{% endcapture %}
{% capture column2 %}
#### 🎵 Symfony

Tag your rule so it's collected automatically, then reference the tagged `RuleApplier` service when wiring the
`GenericChainFactory` for each operation:

```yaml
services:
  App\Etl\Rules\UppercaseFirstRule:
    autowire: true
    tags: [{ name: 'etl.rule' }]
```
{% endcapture %}
{% include block/2column.html column1=column1 column2=column2 %}

Once registered, `UppercaseFirstRuleConfig` works anywhere a `RuleConfigInterface` is accepted, exactly like a
built-in rule:

```php
(new RuleTransformConfig(add: false))
    ->addColumn('FirstName', new UppercaseFirstRuleConfig(new GetRuleConfig('first_name')));
```
