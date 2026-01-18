---
layout: base
title: PHP-ETL - Operations
subTitle: Extract - File Finder
---

The `ExternalFileFinderConfig` operation is the base operation for importing files from **remote or external file systems**. 
It is responsible for **locating files** based on a given pattern and returning them as `FileExtractedItem`s for further processing.

This operation works with any file system supported by [Flysystem](https://flysystem.thephpleague.com/), including **SFTP, local files, AWS S3**, and more.

---

## How It Works

The `ExternalFileFinder` searches a directory for files matching a provided regex pattern. For each file found, 
it returns a `FileExtractedItem`. These items are typically passed down the chain to:

- **Process the file** using `ExternalFileProcessorConfig` to copy it locally
- **Read/process the file** content using format-specific operations (e.g., `CsvExtractConfig`, `JsonExtractConfig`, etc.)

> 📘 Refer to the [Cookbook section](/doc/10-examples/510-import-file.html) for complete examples of end-to-end remote file import flows.

---

## Configuration

Use `ExternalFileFinderConfig` with these parameters:

```php
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\ExternalFileFinderConfig;

$fileFinderConfig = new ExternalFileFinderConfig(
    directory: '/path/to/remote/directory'
);
```

**Parameters:**
- `directory`: The directory path on the file system to search for files

**Input Data:** The operation expects a DataItem containing a regex pattern string to match files.

---

### Registering the Operation

Because multiple instances of this operation may be needed (e.g., different connections or directories), the 
`ExternalFileFinder` must be **manually registered** using a factory with a Flysystem adapter.

{% capture column1 %}
#### 🐘 Standalone

```php
use Oliverde8\Component\PhpEtl\GenericChainFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\Extract\ExternalFileFinderOperation;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\ExternalFileFinderConfig;
use Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem;

// Register with local file system
$chainBuilder = new ChainBuilderV2(
    $executionContextFactory,
    [
        // ... other factories
        new GenericChainFactory(
            ExternalFileFinderOperation::class,
            ExternalFileFinderConfig::class,
            injections: ['fileSystem' => new LocalFileSystem("/")]
        ),
    ]
);
```

**Using SFTP:**
```php
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;

$adapter = new SftpAdapter(
    new SftpConnectionProvider(
        host: 'sftp.example.com',
        username: 'user',
        password: 'password',
        port: 22
    ),
    '/remote/path'
);

new GenericChainFactory(
    ExternalFileFinderOperation::class,
    ExternalFileFinderConfig::class,
    injections: ['fileSystem' => $adapter]
)
```

**Using AWS S3:**
```php
use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;

$client = new S3Client([
    'credentials' => [
        'key'    => 'your-key',
        'secret' => 'your-secret',
    ],
    'region' => 'us-east-1',
    'version' => 'latest',
]);

$adapter = new AwsS3V3Adapter($client, 'your-bucket-name');

new GenericChainFactory(
    ExternalFileFinderOperation::class,
    ExternalFileFinderConfig::class,
    injections: ['fileSystem' => $adapter]
)
```
{% endcapture %}
{% capture column2 %}
#### 🎵 Symfony

In a Symfony application, you should register the operation via Dependency Injection, defining it as a service with the appropriate filesystem adapter.

```yaml
services:
  # Define your filesystem adapter
  app.filesystem.sftp:
    class: League\Flysystem\PhpseclibV3\SftpAdapter
    arguments:
      $connectionProvider: '@app.sftp.connection'
      $root: '/remote/path'

  # Register the ETL operation factory
  app.etl.file_finder.sftp:
    class: Oliverde8\Component\PhpEtl\GenericChainFactory
    arguments:
      $operationClass: 'Oliverde8\Component\PhpEtl\ChainOperation\Extract\ExternalFileFinderOperation'
      $configClass: 'Oliverde8\Component\PhpEtl\OperationConfig\Extract\ExternalFileFinderConfig'
      $injections:
        fileSystem: '@app.filesystem.sftp'
    tags:
      - { name: etl.operation-factory }
```

**With multiple file systems:**
```yaml
services:
  # SFTP File Finder
  app.etl.file_finder.sftp:
    class: Oliverde8\Component\PhpEtl\GenericChainFactory
    arguments:
      $operationClass: 'Oliverde8\Component\PhpEtl\ChainOperation\Extract\ExternalFileFinderOperation'
      $configClass: 'Oliverde8\Component\PhpEtl\OperationConfig\Extract\ExternalFileFinderConfig'
      $injections:
        fileSystem: '@app.filesystem.sftp'
    tags:
      - { name: etl.operation-factory }

  # S3 File Finder  
  app.etl.file_finder.s3:
    class: Oliverde8\Component\PhpEtl\GenericChainFactory
    arguments:
      $operationClass: 'Oliverde8\Component\PhpEtl\ChainOperation\Extract\ExternalFileFinderOperation'
      $configClass: 'Oliverde8\Component\PhpEtl\OperationConfig\Extract\ExternalFileFinderConfig'
      $injections:
        fileSystem: '@app.filesystem.s3'
    tags:
      - { name: etl.operation-factory }
```
{% endcapture %}
{% include block/2column.html column1=column1 column2=column2 %}

---

## Example: Finding and Processing Files

Basic example that finds CSV files matching a pattern and processes them:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\ExternalFileFinderConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\ExternalFileProcessorConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;

$chainConfig = new ChainConfig();

$chainConfig
    // Find files in directory matching pattern
    ->addLink(new ExternalFileFinderConfig(
        directory: '/remote/data/imports'
    ))
    // Copy file locally for processing
    ->addLink(new ExternalFileProcessorConfig())
    // Extract CSV data
    ->addLink(new CsvExtractConfig())
    // Write processed data
    ->addLink(new CsvFileWriterConfig('output.csv'))
    // Clean up local file
    ->addLink(new ExternalFileProcessorConfig());

$chainProcessor = $chainBuilder->createChain($chainConfig);

// Input: regex pattern to match files
$chainProcessor->process(
    new ArrayIterator([
        new DataItem('/^customer_export_[0-9]{8}\.csv$/')  // Matches: customer_export_20231215.csv
    ]),
    []
);
```

## Example: Multiple File Patterns

Process different file types from the same directory:

```php
$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new ExternalFileFinderConfig(directory: '/data/inbox'))
    ->addLink(new ExternalFileProcessorConfig())
    ->addLink(new CallBackTransformerConfig(function(DataItem $item) {
        $data = $item->getData();
        $filename = $data['file'];
        
        echo "Processing: {$filename}\n";
        
        // Route to different processors based on file type
        if (preg_match('/\.csv$/', $filename)) {
            return new DataItem(['file' => $filename, 'type' => 'csv']);
        } elseif (preg_match('/\.json$/', $filename)) {
            return new DataItem(['file' => $filename, 'type' => 'json']);
        }
        
        return $item;
    }))
    ->addLink(new CsvFileWriterConfig('processed-files.csv'));

// Process multiple patterns
$patterns = [
    new DataItem('/^sales_.*\.csv$/'),
    new DataItem('/^inventory_.*\.json$/'),
    new DataItem('/^orders_[0-9]{4}-[0-9]{2}\.csv$/'),
];

$chainProcessor->process(new ArrayIterator($patterns), []);
```

## Example: With Date-Based Filtering

Find files from a specific date range:

```php
$chainConfig = new ChainConfig();

$date = date('Ymd');  // e.g., 20231215

$chainConfig
    ->addLink(new ExternalFileFinderConfig(directory: '/data/daily'))
    ->addLink(new CallBackTransformerConfig(function(DataItem $item) use ($date) {
        $data = $item->getData();
        $filename = basename($data['file']);
        
        // Only process files from today
        if (!preg_match("/_{$date}\./", $filename)) {
            echo "Skipping old file: {$filename}\n";
            return null;  // Skip this file
        }
        
        return $item;
    }))
    ->addLink(new ExternalFileProcessorConfig())
    ->addLink(new CsvExtractConfig())
    ->addLink(new CsvFileWriterConfig("processed_{$date}.csv"));

// Find all CSV files, filter by date in callback
$chainProcessor->process(
    new ArrayIterator([new DataItem('/^report_.*\.csv$/')]),
    []
);
```

## Example: SFTP Import with Error Handling

Robust file import from SFTP with retry logic:

```php
use Oliverde8\Component\PhpEtl\OperationConfig\FailSafeConfig;

$importChain = (new ChainConfig())
    ->addLink(new ExternalFileFinderConfig(directory: '/remote/exports'))
    ->addLink(new ExternalFileProcessorConfig())
    ->addLink(new CsvExtractConfig())
    ->addLink(new CallBackTransformerConfig(function(DataItem $item) {
        // Transform data
        $data = $item->getData();
        // ... processing logic
        return $item;
    }))
    ->addLink(new CsvFileWriterConfig('imported-data.csv'))
    ->addLink(new ExternalFileProcessorConfig()); // Clean up

// Wrap in FailSafe for network reliability
$chainConfig = new ChainConfig();
$chainConfig->addLink(new FailSafeConfig(
    chainConfig: $importChain,
    exceptionsToCatch: [\Exception::class],
    nbAttempts: 3
));

$chainProcessor->process(
    new ArrayIterator([new DataItem('/^export_[0-9]{8}\.csv$/')]),
    []
);
```

## Example: S3 File Import

Import files from AWS S3:

```php
// Assuming S3 adapter is registered with GenericChainFactory

$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new ExternalFileFinderConfig(directory: 'data/incoming'))
    ->addLink(new LogConfig(
        message: 'Found file: @data["file"]',
        level: 'info'
    ))
    ->addLink(new ExternalFileProcessorConfig())
    ->addLink(new CsvExtractConfig())
    ->addLink((new RuleTransformConfig(false))
        ->addColumn('id', [['get' => ['field' => 'ID']]])
        ->addColumn('name', [['get' => ['field' => 'Name']]])
    )
    ->addLink(new CsvFileWriterConfig('s3-import-results.csv'))
    ->addLink(new ExternalFileProcessorConfig());

// Find all CSV files in S3 bucket
$chainProcessor->process(
    new ArrayIterator([new DataItem('/\.csv$/')]),
    []
);
```

## Understanding File Flow

The typical flow when using `ExternalFileFinderConfig`:

1. **Find**: `ExternalFileFinderConfig` searches directory and returns `FileExtractedItem` for each match
2. **Copy**: `ExternalFileProcessorConfig` copies the file to local execution context directory
3. **Process**: Format-specific operations (`CsvExtractConfig`, `JsonExtractConfig`) read the local file
4. **Transform**: Apply transformations to the data
5. **Load**: Save results to output
6. **Cleanup**: `ExternalFileProcessorConfig` removes local copy

```php
$chainConfig
    ->addLink(new ExternalFileFinderConfig(...))      // Step 1: Find
    ->addLink(new ExternalFileProcessorConfig())       // Step 2: Copy locally
    ->addLink(new CsvExtractConfig())                  // Step 3: Read
    ->addLink(new RuleTransformConfig(...))            // Step 4: Transform
    ->addLink(new CsvFileWriterConfig('output.csv'))  // Step 5: Load
    ->addLink(new ExternalFileProcessorConfig());      // Step 6: Cleanup
```

## Best Practices

**1. Use Specific Patterns**
```php
// Good: Specific pattern
new DataItem('/^customer_export_[0-9]{8}\.csv$/')

// Too broad: Might match unwanted files
new DataItem('/customer\.csv/')
```

**2. Always Clean Up**
```php
// Copy file locally
->addLink(new ExternalFileProcessorConfig())
// ... process file ...
// Remove local copy
->addLink(new ExternalFileProcessorConfig())
```

**3. Add Error Handling**
```php
// Wrap in FailSafe for network issues
$chainConfig->addLink(new FailSafeConfig(
    chainConfig: $fileImportChain,
    exceptionsToCatch: [\Exception::class],
    nbAttempts: 3
));
```

**4. Log File Processing**
```php
->addLink(new LogConfig(
    message: 'Processing file: @data["file"], size: @data["size"] bytes',
    level: 'info'
))
```

**5. Validate Files Before Processing**
```php
->addLink(new CallBackTransformerConfig(function(DataItem $item) {
    $data = $item->getData();
    
    // Validate file size
    if ($data['size'] > 100 * 1024 * 1024) { // 100MB
        throw new \RuntimeException('File too large');
    }
    
    return $item;
}))
```

## Common Use Cases

- **SFTP Import**: Fetch files from SFTP servers (partners, vendors)
- **S3 Import**: Process files uploaded to AWS S3 buckets
- **FTP Import**: Import files from legacy FTP servers
- **Azure Blob**: Process files from Azure Blob Storage
- **Multi-Source**: Import from multiple remote locations
- **Scheduled Imports**: Daily/hourly file imports from remote systems
- **EDI Processing**: Import EDI files from partner systems
- **Data Lake**: Process files from data lake storage
