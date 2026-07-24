---
layout: base
title: PHP-ETL - Operations
subTitle: Aggregation/Grouping - Batch
---

The `BatchConfig` operation collects items into fixed-size chunks, emitting one item per chunk as soon as it's
full. Unlike `simple-grouping`, it doesn't wait for the end of the stream and doesn't buffer more than `size`
items at a time — useful for bulk inserts or bulk API calls where you want to process N records at once
without loading the whole dataset into memory.

## Options

- **size:** The number of items to collect before emitting a batch.

## Example

Here's an example of batching customers into groups of 100 for a bulk database insert.

**Configuration:**

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Grouping\BatchConfig;

$chainConfig = new ChainConfig();
$chainConfig->addLink(new BatchConfig(size: 100));

// The next operation receives one DataItem per 100 input items,
// each containing an array of up to 100 raw records.
```

**Behavior:**

- Every 100 items received, the operation emits a single item whose data is an array of those 100 records.
- While a batch is filling up, incoming items produce a `ChainBreakItem` and don't reach the next operation.
- If the stream ends with a partial batch (fewer than `size` items buffered), that smaller batch is emitted
  once processing finishes, so no data is lost.

## Common Use Cases

- **Bulk database inserts**: Accumulate N records, then insert them in a single query
- **Bulk API calls**: Batch records into a single request instead of one call per item
- **Rate-limited processing**: Process items in controlled chunks
