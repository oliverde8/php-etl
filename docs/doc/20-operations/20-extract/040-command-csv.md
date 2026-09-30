---
layout: base
title: PHP-ETL - Operations
subTitle: Extract - CSV From A Command
---

The `CommandCsvExtractConfig` operation runs an external command on a file and reads the command's output as CSV.
It lets a fast CLI tool such as [xan](https://github.com/medialab/xan) do the heavy work (decompressing, parsing,
filtering, selecting columns) while the chain keeps the business logic.

On 1M rows, filtering with xan takes 0.5s, against 12.6s with `CsvExtractConfig` + `FilterDataConfig`.
See the [benchmark](/doc/10-examples/520-benchmark.html).

---

## Configuration

```php
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CommandCsvExtractConfig;

$config = new CommandCsvExtractConfig(
    command: ['xan', 'filter', '-d', ';', 'keep == 1', '{file}'],
    delimiter: ',',      // Delimiter of the command OUTPUT (default: ',')
    enclosure: '"',      // (default: '"')
    escape: '',          // (default: '', RFC 4180 quoting)
    fileKey: 'file',     // Key containing file path (default: 'file')
    columns: null,       // Columns to keep, null keeps all (default: null)
    idleTimeout: null    // Seconds without activity before failing, null waits forever (default: null)
);
```

**Parameters:**
- `command`: The command as a list of arguments. It is run without a shell, so arguments are never interpreted.
  Pass a list of commands to pipe them into each other: `[['gzip', '-dc'], ['xan', 'filter', 'keep == 1']]`.
- `delimiter`: Delimiter of what the command writes. xan always writes `,`, whatever its input delimiter (`-d`).
- `columns`: Same as for `CsvExtractConfig`, applied to the command's output.
- `idleTimeout`: Fails when the command neither outputs a row nor accepts input for that many seconds. On a
  local file with a very selective filter, xan can stay silent for a long time while working, so keep it
  generous.

## How the file is given to the command

The operation always opens the file through the execution context's file system, then:

- **Local file**: `{file}` is replaced by the absolute path of the file. The tool reads it directly, so xan
  detects `.gz` files by their extension. The file is also given as stdin.
- **Remote file** (Flysystem S3, SFTP...): `{file}` is replaced by `-` and PHP streams the file into the
  command's stdin. There is no temporary copy.

The same command works for both cases, except for compressed files: from stdin xan can't see the extension, so
decompress first with a pipeline.

```php
// Local .gz file
new CommandCsvExtractConfig(['xan', 'filter', '-d', ';', 'keep == 1', '{file}']);

// Remote .gz file
new CommandCsvExtractConfig([
    ['gzip', '-dc'],
    ['xan', 'filter', '-d', ';', 'keep == 1'],
]);
```

## Errors

- If a command exits with a non-zero code, a `CommandException` is thrown with the command's stderr.
- If the chain stops before reading all rows (exception, chain break), the commands are terminated.
- If `idleTimeout` is reached, or the remote input stream times out, a `CommandException` is thrown and the
  commands are terminated.
- Memory stays constant whatever the file size: data flows through small buffers. For remote files, this
  depends on the file system adapter actually streaming in `readStream()`.

## Example: Filter And Select With xan

```php
$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new CommandCsvExtractConfig(
        ['xan', 'filter', '-d', ';', 'Status == "Active"', '{file}'],
        columns: ['ID', 'Name', 'Email']
    ))
    ->addLink(new CsvFileWriterConfig('active-customers.csv'));

$chainProcessor->process(
    new ArrayIterator([new DataItem(['file' => 'customers.csv.gz'])]),
    []
);
```
