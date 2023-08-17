# Contextual chain using Yaml!

In our [previous examples](./DescribeChain.md) our chain had access to the whole file system. This means having
multiple chains running together, or having a list of files each execution is generated is impossible. 

Both the **Symfony Bundle** and the **Magento2 Module** will use contextual chains. This means the "main" operations
have only access to a particular directory created for the execution of the chain. 

Additional operations such as the `ExternalFileFinderOperation` and `ExternalFileProcessor` will be use to process
files that are either on a remote directory (sftp, bucket s3...) or files that are on the local file system. Because 
operations such as the `CsvLoader` will not have access to those files unless they are copied into the contextual 
directory of the current execution. 

Let start by a simple example. 

## Example 01 - Write the result of an API to a CSV File. 

This examples chain is identical to the one we have seen in the (non contextual example #8)[./DescribeChain.md#Example 08 - Write the result of an API to a CSV File.]

But in this example the output file will be written to a unique directory. 

For this we will first create a new ContextFactory using `PerExecutionContextFactory`. This context factory will
create unique contexts for each execution. 

```php
<?php
    $workdir = __DIR__ . "/../../../var/";
    $dirManager = new ChainWorkDirManager($workdir);
    $loggerFactory = new NullLoggerFactory();
    $fileFactory = new LocalFileSystemFactory($dirManager);

    return new \Oliverde8\Component\PhpEtl\PerExecutionContextFactory($dirManager, $fileFactory, $loggerFactory);
```

The execution is identified with objects of type `ExecutionInterface` set on the processor: 

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

Executing this  `php docs/examples/10-contextual/01-api-to-csv.php` will create a directory in `var/` with the output result. 

## Example 02 - Fetch a csv file from another file system. 

The ETL can be used to fetch files from another filesystem and process them using the ETL. Files will be moved into 
`processing` and `processed` directories as the ETL runs. 

We will use the `ExternalFileFinderOperation` to find the files. And use the `ExternalFileProcessorOperation` to copy 
the files to the context of the ETL execution. 

```yaml
  find-file1:
    operation: external-file-finder-local
    options:
      directory: "@context['dir']"

  process-new-file1:
    operation: external-file-processor
    options: []
```

The file finder will use a directory that we will add to the context at the beginning of the execution. 

```php
$options = [
    // ...
    'dir' => $dir,
];
```

We will also need to provide the finder with a regex as chain input so that it can find all the files. 
We end up with something like this: 

```php
$chainProcessor->process(
    new ArrayIterator(['/^file[0-9]\.csv$/']),
    $options
);
```

Once the file is in the execution context we can read the file and process it as we have seen in previous examples. 

```yaml
  read-file:
    operation: csv-read
    options: [] # The default delimeter,&
```

The `external-file-processor` will move the file in the external file system into a `processing` folder. Once 
our chain has finished processing the file we will move it into another directory `processed`. Adding a second
`external-file-processor` at the end of our chain will move the file.

```yaml
  process-finalized-file1:
    operation: external-file-processor
    options: []
```

> This works as the `external-file-processor` will return at first a DataItem with the name of the file and a 
> ExternalFileItem. The first DataItem will be processed, by the rest of the chain. Once that is finished then the 
> ExternalFileItem will reach the second `external-file-processor` which will detect that the file is being processed
> and will move the file into the `processed` directory.

You can test this rule yourself, check the [transform yml](examples/10-contextual/02-find-file.yml)
and by executing `php examples/10-contextual/02-find-file.php`. This will initialize a new csv file in 
`examples/10-contextual/02-find-file/` before executing the etl. 

## Example 03 - Using a true External file system

When PHPEtl is used with an execution context all files outside the execution context directory are considered external.

In our previous example we have used files that are locally available. But most probably we would like to find and
process files stored remotely. The ETL has a adapter allowing it to use the [FlySystem](https://flysystem.thephpleague.com/docs/)
library for this. 

This adapter can be used for 2 purposes:
- Process external files stored remotely.
- Allow the ETL context to be stored remotely for installations that has multiple servers or to better secure the data. 

In this example only the first point interest us. In the previous example the file finder was defined as follows:
```php
    $builder->registerFactory(
        new ExternalFileFinderFactory(
            'external-file-finder-local', 
            ExternalFileFinderOperation::class, 
            new LocalFileSystem("/")
        )
    );
```

We are using here the LocalFileSystem. We can easily replace this with the FlySystemFileSystem:

```php
    $builder->registerFactory(
        new ExternalFileFinderFactory(
            'my-file-finder-local', 
            ExternalFileFinderOperation::class, 
            new FlySystemFileSystem(
                new Filesystem(
                    new SftpAdapter(
                        // ...
                    ),
                    // ...
                )
            )
        )
    );
```

You might have noticed that only the `ExternalFileFinder` requires a FileSystem. The `ExternalFileProcessor` **does not**. 
This is because the `ExternalFileItem` returned by the finder holds the FileSystem in which the file was found as well.
This makes other operations that needs to interact with found files much easier to use. 
