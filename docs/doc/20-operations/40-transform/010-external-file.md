---
layout: base
title: PHP-ETL - Operations
subTitle: Transform - External File Processor
---

The **External File Processor** operation moves and registers external files (e.g., from SFTP, local FS, cloud storage) into the ETL execution context. This operation works hand-in-hand with the `ExternalFileFinderConfig` and is essential for enabling further processing of remote files.

## Purpose

When `ExternalFileFinderConfig` locates a remote file, it returns an `ExternalFileItem`. However, that file is not yet part of the ETL's working context.

The `ExternalFileProcessorConfig` operation:

- **Copies the external file into the ETL context**, making it accessible to extract operations like `CsvExtractConfig`, `JsonExtractConfig`, etc.
- **Archives the file** within the ETL execution history, so it can be tracked and audited later.
- **Returns a `DataItem`** containing the path of the new local file, making it usable by downstream operations.

## File Lifecycle & Behavior

The operation follows a structured file management flow across multiple runs:

1. **Initial Processing**:
   - The file is moved from its source directory into a `processing/` subdirectory (within the external filesystem).
   - It is also copied to the local ETL context (temporary working directory).
   - A `DataItem` is emitted with the new local file path.

2. **Post-Processing**:
   - If the operation is used a second time in the same chain (e.g., near the end of the flow), it will:
     - Move the remote file from `processing/` to `processed/`, effectively archiving it.
     - This signals the file has been fully and successfully handled.

> 💡 **Best Practice**:  
> Use `ExternalFileProcessorConfig` **twice** in a chain:
> - Once immediately after the `ExternalFileFinderConfig`.
> - Once at the end of the chain, to archive the file remotely after successful processing.

## Filesystem Agnostic

The operation does **not require manual configuration of the filesystem**. It uses the File system instance already embedded in the `ExternalFileItem` provided by the `ExternalFileFinderConfig`.

---

## Configuration

### Basic Configuration

```php
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\ExternalFileProcessorConfig;

$config = new ExternalFileProcessorConfig();
```

### Constructor Parameters

```php
public function __construct(
    string $flavor = 'default'
)
```

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `flavor` | `string` | `'default'` | Operation flavor for custom implementations |

---

## Input/Output

- **Input**: Expects `ExternalFileItem` objects from `ExternalFileFinderConfig`
- **Output**: Produces `DataItem` objects containing local file paths

---

## Examples

### Example 1: Basic File Processing

Process files from a local directory, move them through processing stages:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\ExternalFileFinderConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\ExternalFileProcessorConfig;

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new ExternalFileFinderConfig(directory: '/data/incoming'))
    ->addLink(new ExternalFileProcessorConfig()) // Copy to local context
    ->addLink(new CsvExtractConfig())
    ->addLink(new CsvFileWriterConfig('output.csv'))
    ->addLink(new ExternalFileProcessorConfig()); // Archive to processed/

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem('/^orders_.*\.csv$/')]),
    ['dir' => '/data/incoming']
);
```

**What happens:**
1. `ExternalFileFinderConfig` finds files matching the pattern in `/data/incoming`
2. First `ExternalFileProcessorConfig` moves files to `processing/` and copies to local context
3. Files are processed (CSV extraction and transformation)
4. Second `ExternalFileProcessorConfig` moves files from `processing/` to `processed/`

### Example 2: SFTP File Processing

Process files from an SFTP server with error handling:

The filesystem is not a per-config option — it's injected into the `GenericChainFactory` that builds `ExternalFileFinderOperation`, with a distinct [flavor](/doc/01-understand-the-etl/flavor.html) per adapter:

```php
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Filesystem;
use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\GenericChainFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\Extract\ExternalFileFinderOperation;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\ExternalFileFinderConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\ExternalFileProcessorConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

// Create SFTP filesystem
$adapter = new FtpAdapter(
    FtpConnectionOptions::fromArray([
        'host' => 'sftp.example.com',
        'username' => 'user',
        'password' => 'pass',
        'port' => 22,
    ])
);
$filesystem = new Filesystem($adapter);

// Register a factory for this adapter under its own flavor
$chainBuilder = new ChainBuilderV2($executionContextFactory, [
    // ... other factories
    new GenericChainFactory(
        ExternalFileFinderOperation::class,
        ExternalFileFinderConfig::class,
        flavor: 'sftp',
        injections: ['fileSystem' => $filesystem]
    ),
]);

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new ExternalFileFinderConfig(directory: '/uploads', flavor: 'sftp'))
    ->addLink(new ExternalFileProcessorConfig())
    ->addLink(new CsvExtractConfig(delimiter: ';'))
    ->addLink((new RuleTransformConfig(add: true))
        ->addColumn('status', [['constant' => ['value' => 'processed']]])
    )
    ->addLink(new CsvFileWriterConfig('processed_orders.csv'))
    ->addLink(new ExternalFileProcessorConfig());

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem('/^order_.*\.csv$/')]),
    []
);
```

### Example 3: Multiple File Types with Conditional Processing

Process different file types differently after initial file processor:

`ChainSplitConfig` branches all receive the same item — there's no per-branch condition on `addSplit()`. To route by file type, filter each branch with a callback that breaks the chain for files it doesn't handle:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\ChainBreakItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\ExternalFileFinderConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\ExternalFileProcessorConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainSplitConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\JsonExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$csvChain = new ChainConfig();
$csvChain
    ->addLink(new CallBackTransformerConfig(function (DataItem $item) {
        return str_ends_with($item->getData(), '.csv') ? $item : new ChainBreakItem();
    }))
    ->addLink(new CsvExtractConfig())
    ->addLink(new CsvFileWriterConfig('csv_output.csv'));

$jsonChain = new ChainConfig();
$jsonChain
    ->addLink(new CallBackTransformerConfig(function (DataItem $item) {
        return str_ends_with($item->getData(), '.json') ? $item : new ChainBreakItem();
    }))
    ->addLink(new JsonExtractConfig())
    ->addLink(new CsvFileWriterConfig('json_output.csv'));

$mainChain = new ChainConfig();
$mainChain
    ->addLink(new ExternalFileFinderConfig(directory: '/data/mixed'))
    ->addLink(new ExternalFileProcessorConfig())
    ->addLink((new ChainSplitConfig())
        ->addSplit($csvChain)
        ->addSplit($jsonChain)
    )
    ->addLink(new ExternalFileProcessorConfig());

$chainProcessor = $chainBuilder->createChain($mainChain);
$chainProcessor->process(
    new ArrayIterator([new DataItem('/^data_.*\.(csv|json)$/')]),
    []
);
```

### Example 4: Processing with Validation

Validate files before processing and only archive valid ones:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\ExternalFileFinderConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\ExternalFileProcessorConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new ExternalFileFinderConfig(directory: '/data/incoming'))
    ->addLink(new ExternalFileProcessorConfig())
    ->addLink(new CsvExtractConfig())
    // Validate: only keep rows with required fields
    ->addLink(new FilterDataConfig(
        'item.id != null and item.name != null and item.email != null'
    ))
    // Transform: add validation status
    ->addLink(new CallBackTransformerConfig(
        function($item, $context) {
            $item['validated'] = true;
            $item['validation_date'] = date('Y-m-d H:i:s');
            return $item;
        }
    ))
    ->addLink(new CsvFileWriterConfig('validated_output.csv'))
    ->addLink(new ExternalFileProcessorConfig()); // Only archive if processing succeeded

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem('/^customers_.*\.csv$/')]),
    []
);
```

### Example 5: Re-processing Failed Files

Process files that failed in previous runs:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\ExternalFileFinderConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\ExternalFileProcessorConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\FailSafeConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

// Create chain with error handling
$processingChain = new ChainConfig();
$processingChain
    ->addLink(new CsvExtractConfig())
    ->addLink(new CsvFileWriterConfig('output.csv'));

$mainChain = new ChainConfig();
$mainChain
    // Files stuck in processing/ from a previously failed run need their own finder pass
    ->addLink(new ExternalFileFinderConfig(directory: '/data/incoming'))
    ->addLink(new ExternalFileProcessorConfig())
    ->addLink(new FailSafeConfig(
        chainConfig: $processingChain,
        exceptionsToCatch: [\Exception::class],
        nbAttempts: 3
    ))
    ->addLink(new ExternalFileProcessorConfig());

$chainProcessor = $chainBuilder->createChain($mainChain);
$chainProcessor->process(
    new ArrayIterator([new DataItem('/^data_.*\.csv$/')]),
    []
);
```

**Note**: `ExternalFileFinderConfig` only searches one `directory`. To retry files stuck in `processing/` from a failed previous run, add a second `ExternalFileFinderConfig` link (or a separate chain run) pointed at that path.

---

## File Lifecycle Details

### Directory Structure

The operation creates and uses the following directory structure:

```
/data/incoming/          # Original files arrive here
├── file1.csv
├── file2.csv
└── processing/          # Files being processed
    └── file1.csv
└── processed/           # Successfully processed files
    └── file1.csv
```

### State Transitions

1. **Found**: File is discovered by `ExternalFileFinderConfig` in the source directory
2. **Processing**: First `ExternalFileProcessorConfig` moves file to `processing/` subdirectory
3. **Processed**: Second `ExternalFileProcessorConfig` moves file to `processed/` subdirectory

---

## Best Practices

### 1. Always Use Twice

Always use `ExternalFileProcessorConfig` twice in your chain:

```php
$chainConfig
    ->addLink(new ExternalFileFinderConfig(...))
    ->addLink(new ExternalFileProcessorConfig()) // Move to processing/
    // ... processing operations ...
    ->addLink(new ExternalFileProcessorConfig()); // Archive to processed/
```

This ensures proper file lifecycle management.

### 2. Log File Processing

Add logging to track file processing:

```php
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\LogConfig;

$chainConfig
    ->addLink(new ExternalFileFinderConfig(...))
    ->addLink(new LogConfig('Found file: {item.filename}'))
    ->addLink(new ExternalFileProcessorConfig())
    ->addLink(new LogConfig('Processing file: {item.data}'))
    // ... processing ...
    ->addLink(new LogConfig('Completed file: {item.data}'))
    ->addLink(new ExternalFileProcessorConfig());
```

## Related Operations

- [ExternalFileFinderConfig](../20-extract/010-file-finder.html) - Find files in external filesystems
- [CsvExtractConfig](../20-extract/020-csv.html) - Extract data from CSV files
- [JsonExtractConfig](../20-extract/030-json.html) - Extract data from JSON files
- [FailSafeConfig](../10-building/040-safe.html) - Handle errors during processing
