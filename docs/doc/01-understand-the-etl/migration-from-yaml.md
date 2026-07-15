---
layout: base
title: PHP-ETL - Understand the ETL
subTitle: Migrating from YAML
width: large
---

## Migrating from YAML to PHP configuration

Before 2.0, chains were described as YAML (or plain arrays) and built with `ChainBuilder`. Since 2.0, chains are
described with typed PHP objects and built with `ChainBuilderV2`. The YAML approach still works but is deprecated —
using `ChainBuilder` now triggers a deprecation notice.

Why the change: typed config classes give you IDE autocompletion and constructor-time validation, instead of typos
in array keys that only fail at runtime.

### Before (YAML)

```yaml
chain:
  read-file:
    operation: csv-read
    options: []

  keep-only-name-and-subscription:
    operation: rule-engine-transformer
    options:
      add: false
      columns:
        Name:
          rules:
            - implode:
                values:
                  - [{get: {field: 'FirstName'}}]
                  - [{get: {field: 'LastName'}}]
                with: " "
        SubscriptionStatus:
          rules:
            - get: {field: 'IsSubscribed'}

  write-new-file:
    operation: csv-write
    options:
      file: "data/output.csv"
```

```php
$chainBuilder = new ChainBuilder($executionContextFactory);
$chainBuilder->registerFactory(new CsvExtractFactory('csv-read', CsvExtractOperation::class));
$chainBuilder->registerFactory(new RuleTransformFactory('rule-engine-transformer', RuleTransformOperation::class, $ruleApplier));
$chainBuilder->registerFactory(new CsvFileWriterFactory('csv-write', FileWriterOperation::class));

$config = Yaml::parseFile('chain.yml');
$chainProcessor = $chainBuilder->buildChainProcessor($config['chain']);
```

### After (PHP)

```php
$chainConfig = (new ChainConfig())
    ->addLink(new CsvExtractConfig())
    ->addLink((new RuleTransformConfig(add: false))
        ->addColumn('Name', [
            ['implode' => [
                'values' => [
                    [['get' => ['field' => 'FirstName']]],
                    [['get' => ['field' => 'LastName']]],
                ],
                'with' => ' ',
            ]],
        ])
        ->addColumn('SubscriptionStatus', [['get' => ['field' => 'IsSubscribed']]])
    )
    ->addLink(new CsvFileWriterConfig('data/output.csv'));

$chainBuilder = new ChainBuilderV2($executionContextFactory, [
    new GenericChainFactory(CsvExtractOperation::class, CsvExtractConfig::class),
    new GenericChainFactory(RuleTransformOperation::class, RuleTransformConfig::class, injections: ['ruleApplier' => $ruleApplier]),
    new GenericChainFactory(FileWriterOperation::class, CsvFileWriterConfig::class),
]);

$chainProcessor = $chainBuilder->createChain($chainConfig);
```

Each YAML `operation` key maps to one `GenericChainFactory` registration: the operation class stays the same, only
the factory changes from a hand-written `AbstractFactory` subclass to a declarative registration. See
[Getting Started](/doc/getting-started/standalone.html) for the full list of built-in operations and their config
classes.

### What doesn't carry over automatically

`ChainBuilder` and `ChainBuilderV2` are separate registries with separate operation factories. You can't reference a
YAML-declared operation from a `ChainConfig`, or vice versa — pick one paradigm per chain builder instance. If you
have both old and new chains running side by side, keep two `ChainBuilder`/`ChainBuilderV2` instances.

The full runnable before/after examples live in the repo under `examples/01-SimpleYamlCases/` (old) and
`examples/00-SimpleCases/` (new) — same scenarios, side by side.
