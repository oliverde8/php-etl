---
layout: base
title: PHP-ETL - Getting Started
subTitle: Standalone 
---

1. Start by installing the necessary dependencies

```bash
composer require oliverde8/php-etl
```

2. You will need to initialize all your operations. 

```php
    $ruleApplier = new \Oliverde8\Component\RuleEngine\RuleApplier(
        new \Psr\Log\NullLogger(),
        [
            new \Oliverde8\Component\RuleEngine\Rules\Get(new \Psr\Log\NullLogger()),
            new \Oliverde8\Component\RuleEngine\Rules\Implode(new \Psr\Log\NullLogger()),
            new \Oliverde8\Component\RuleEngine\Rules\StrToLower(new \Psr\Log\NullLogger()),
            new \Oliverde8\Component\RuleEngine\Rules\StrToUpper(new \Psr\Log\NullLogger()),
            new \Oliverde8\Component\RuleEngine\Rules\ExpressionLanguage(new \Psr\Log\NullLogger()),
        ]
    );

    $builder = new ChainBuilder(getExecutionContextFactory());
    $builder->registerFactory(new RuleTransformFactory('rule-engine-transformer', RuleTransformOperation::class, $ruleApplier));
    $builder->registerFactory(new FilterDataFactory('filter', FilterDataOperation::class, $ruleApplier));
    $builder->registerFactory(new SimpleGroupingFactory('simple-grouping', SimpleGroupingOperation::class));
    $builder->registerFactory(new ChainSplitFactory('split', ChainSplitOperation::class, $builder));
    $builder->registerFactory(new CsvFileWriterFactory('csv-write', FileWriterOperation::class));
    $builder->registerFactory(new JsonFileWriterFactory('json-write', FileWriterOperation::class));
    $builder->registerFactory(new CsvExtractFactory('csv-read', CsvExtractOperation::class));
    $builder->registerFactory(new JsonExtractFactory('json-read', JsonExtractOperation::class));
    $builder->registerFactory(new SplitItemFactory('split-item', SplitItemOperation::class));
    $builder->registerFactory(new SimpleHttpOperationFactory('http', SimpleHttpOperation::class));
    $builder->registerFactory(new ExternalFileFinderFactory('external-file-finder-local', ExternalFileFinderOperation::class, new LocalFileSystem("/")));
    $builder->registerFactory(new ExternalFileProcessorFactory("external-file-processor", ExternalFileProcessorOperation::class));

```
