---
layout: base
title: PHP-ETL - Cook Books
subTitle: Benchmark - Large CSV
---

### Benchmark - Large CSV

The scripts in `examples/20-Benchmark/` measure how long a simple chain takes on a large CSV file,
compared with a plain PHP loop doing the same work.

Every script reads the file, keeps the rows where `keep` is `1` (5% of them) and writes them to a new CSV.

| Script | What it runs |
|---|---|
| `00-RawFgetcsv.php` | Plain `fgetcsv` loop with no ETL. This is the baseline. |
| `01-ExtractFilterWrite.php` | `CsvExtractConfig` → `FilterDataConfig` → `CsvFileWriterConfig` |
| `02-GzipExtract.php` | Same chain, reading `bench.csv.gz` with `compression: 'gzip'` |
| `03-ProjectionExtract.php` | Same chain, keeping only 3 of the 30 columns with `columns` |
| `04-XanExtract.php` | `CommandCsvExtractConfig` running `xan filter` on the local `.gz` file |
| `05-XanExtractRemote.php` | Same with a remote-like file system: the file is streamed into `gzip -dc \| xan filter` |

The chain scripts print the time spent in each operation using the chain observer (the third argument of `process()`).

#### Running it

```bash
cd examples/20-Benchmark
php generate.php 1000000   # 1M rows, 30 columns: data/bench.csv (424 MB) and data/bench.csv.gz (66 MB)
php 00-RawFgetcsv.php
php 01-ExtractFilterWrite.php
php 02-GzipExtract.php
php 03-ProjectionExtract.php
php 04-XanExtract.php          # requires xan
php 05-XanExtractRemote.php    # requires xan
```

The scripts must be run from the `20-Benchmark` directory, since paths are relative to it.

#### Results

1M rows, PHP 8.5, Apple Silicon:

| Script | Total | Peak memory |
|---|---|---|
| `00-RawFgetcsv.php` | 5.2s | 2 MB |
| `01-ExtractFilterWrite.php` | 12.6s | 4 MB |
| `02-GzipExtract.php` | 12.8s | 4 MB |
| `03-ProjectionExtract.php` | 12.3s | 4 MB |
| `04-XanExtract.php` | 0.5s | 4 MB |
| `05-XanExtractRemote.php` | 0.55s | 4 MB |

Observer output for `01-ExtractFilterWrite.php`:

```
Operation                         Processed     Returned  Time (ms)
extract                                   1            1          0
filter                              1000001        50001       5522
write                                 50001        50001        290
```

What this shows:

- The chain is about 2.4× slower than the plain loop. The filter's expression (Symfony Expression Language) accounts
  for 5.5s of that, which is where to look first on large files.
- `extract` shows 0ms because rows are read lazily: parsing happens while the chain pulls rows, so it isn't counted
  against the extract operation.
- Reading the gzip file directly costs about 0.2s more and saves 358 MB of disk.
- `columns` only saves a little, because `fgetcsv` still parses every field. It helps more when later operations
  work on the whole row.
- Letting xan filter the rows ([CommandCsvExtractConfig](/doc/20-operations/20-extract/040-command-csv.html)) is
  about 25× faster: PHP only handles the 50k rows that are kept.
