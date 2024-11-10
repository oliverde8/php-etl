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

Additional operations such as the ExternalFileFinderOperation and ExternalFileProcessor will be use to 
process files that are either on a remote directory (sftp, bucket s3...) or files that are on the local file system. 
Because operations such as the CsvLoader will not have access to those files unless they are copied into the contextual directory of the current execution.

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
The execution is identified with objects of type ExecutionInterface set on the processor:
{% endcapture %}
{% capture code %}
```php
$options = [
    'etl' => [
        'execution' => new PockExecution(new DateTime())
    ]
];

$chainProcessor->process(
    new ArrayIterator([[]]),
    $options
);
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

Executing this will create a directory in `var/` with the output result. Everytime you execute the chain a new
directory wil be created.

