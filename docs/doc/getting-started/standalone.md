---
layout: base
title: PHP-ETL - Getting Started
subTitle: Standalone 
---

## Introduction

You probably will never run the ETL in a stand-alone mode, but if you do intend to create an adapter
for a different Framework or CMS you need to understand how to start the ETL from "nothing". So this document 
focused on that aspect. 

That said that does not mean the ETL can't be run standalone, it's just that the "init" part is not ideal. 

## Writing some code

- Start by installing the necessary dependencies
```sh
    composer require oliverde8/php-etl
```

- We will also need a factgory to create execution context's, you can read more about what an execution context is
[here](/doc/01-understand-the-etl/execution-context.html). 
```php
$executionContextFaxtory = new ExecutionContextFactory();
```

> ##### TIP
> The default provider we use here is very simple and basically bypasses the context's.
{: .block-tip }

- You will need to initialize all your operations.
```php
    // We need to create the rule applier operaiton with all the rules we have. Additional rules can be added.
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

> ##### TIP
> We are here initializing all possible operations, most of the documentation
> will assume you are using symfony as framework, you will need to register your factories manually
> if you are not.
{: .block-tip }


- We can start describing our etl in a `Yaml` file. We will create here a single step chain that just dumps the data.
```yaml
chain:
  dump-data:
    operation: dump
    options: []
```

- We can now build our etl
```php
$chainProcessor = $builder->buildChainProcessor(Yaml::parse(file_get_contents($fileName)),[]);
```

- Before starting the etl we need our input data
```php
$inputData = [['myKey' => "value1"], ['myKey' => "value1"]]
```

- We can now start it
```php
$chainProcessor->process(new ArrayIterator($inputData));
```

## Conclusion

We have created a very simple ETL that only outputs to the console the data we have given it. To move on further you 
should read: 

- [The basic conecpt of the ETL](/doc/01-understand-the-etl/the-concept.html)
- [Why to have an execution context & what it does](/doc/01-understand-the-etl/execution-context.html)