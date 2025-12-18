---
layout: base
title: PHP-ETL - Understand the ETL
subTitle: Execution Context - Why to have an execution context & what it does
width: large
---

## Execution Context - Why to have an execution context & what it does

In most of our examples our chain had access to the whole file system. 
This means having multiple chains running together, or having a list of files each execution has generated is impossible.

Both the 🎵 Symfony Bundle(and therefore the 🦢 Sylius integration) and the Magento2 Module will use contextual chains.
This means the "main" operations have only access to a particular directory created for the execution of the chain.

This directory might be locally available on the server or it might be a remote file system. This can be usefull if 
php-etl is used on a multi server setup for example to share files between the servers. 

Additional operations such as the `ExternalFileFinderOperation` and `ExternalFileProcessor` will be use to 
process files that are either on a remote directory (sftp, bucket s3...) or files that are on the local file system. 
Because operations such as the CsvLoader will not have access to those files unless they are copied into the contextual directory of the current execution.

So both or ExternalFile & our context can be a remote, they could be the same remote, or 2 different remotes.

Let start by a simple example.

### Write the result of an API to a CSV File.

{% capture description %}
For this we will first create a new ContextFactory using PerExecutionContextFactory. 
This context factory will create unique contexts for each execution. This means a unique directory to run the etl
in; and a unique logger. 

This is only needed if you are running the etl in **🐘 standalone**. With any integration this should be automatically
handled for you.
{% endcapture %}
{% capture code %}
```php
<?php
use Oliverde8\Component\PhpEtl\ChainWorkDirManager;
use Oliverde8\Component\PhpEtl\Factory\NullLoggerFactory;
use Oliverde8\Component\PhpEtl\Factory\LocalFileSystemFactory;
use Oliverde8\Component\PhpEtl\PerExecutionContextFactory;

$workdir = __DIR__ . "/var/";
$dirManager = new ChainWorkDirManager($workdir);
$loggerFactory = new NullLoggerFactory();
$fileFactory = new LocalFileSystemFactory($dirManager);

return new PerExecutionContextFactory(
        $dirManager,
        $fileFactory,
        $loggerFactory
    );
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

{% capture description %}
Next, we configure the ETL chain using the new type-safe configuration approach. We create a ChainConfig object
and add operation configurations to it. Each operation is configured using typed configuration classes with
named parameters for better IDE support and validation.
{% endcapture %}
{% capture code %}
```php
<?php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SplitItemConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$chainConfig = new ChainConfig();
$chainConfig->addLink(new SimpleHttpConfig(
        method: 'GET',
        url: 'https://63b687951907f863aaf90ab1.mockapi.io/test',
        responseIsJson: true
    ))
    ->addLink(new SplitItemConfig(
        keys: ['content'],
        singleElement: true
    ))
    ->addLink(new CsvFileWriterConfig('output.csv'));
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

{% capture description %}
Finally, we create the chain processor from our configuration and execute it with the execution context. 
The execution is identified with objects of type ExecutionInterface set in the options array:
{% endcapture %}
{% capture code %}
```php
use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Model\PockExecution;

// Assuming $chainBuilder is a configured ChainBuilderV2 instance
$chainProcessor = $chainBuilder->createChain($chainConfig);

$options = [
    'etl' => [
        'execution' => new PockExecution(new DateTime())
    ]
];

$chainProcessor->process(
    new ArrayIterator([new DataItem([])]),
    $options
);
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

Executing this will create a directory in `var/` with the output result. Everytime you execute the chain a new
directory wil be created.

