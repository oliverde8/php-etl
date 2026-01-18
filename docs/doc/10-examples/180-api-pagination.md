---
layout: base
title: PHP-ETL - Cook Books
subTitle: API Pagination with ChainRepeat
width: large
---

### API Pagination with ChainRepeat

A very common use case when working with APIs is pagination. Most APIs return data in pages, requiring multiple 
requests to fetch all data. The `ChainRepeatConfig` operation is designed specifically for this scenario.

## How ChainRepeat Works

The `ChainRepeat` operation executes a sub-chain repeatedly until a validation condition returns false. This makes it 
perfect for:
- Fetching all pages from a paginated API
- Retrying failed operations
- Processing data in iterative batches

### Basic Pagination Example

{% capture description %}
Let's fetch data from a paginated API. The key is to use a validation expression that checks if there's a next page.
The repeat chain will execute until `hasNextPage` becomes false.

We use a `CallBackTransformerConfig` to simulate API calls, but in a real scenario, you would use `SimpleHttpConfig`.
{% endcapture %}
{% capture code %}
```php
use Oliverde8\Component\PhpEtl\OperationConfig\ChainRepeatConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\DataItem;

$page = 1;

$repeatConfig = new ChainRepeatConfig(
    chainConfig: (new ChainConfig())
        ->addLink(new CallBackTransformerConfig(function(DataItemInterface $dataItem) use (&$page) {
            echo "Fetching page {$page}...\n";
            
            // Simulate API response
            $response = [
                'items' => [...], // Your data items
                'page' => $page,
                'hasNextPage' => $page < 5, // Continue until page 5
            ];
            
            $page++;
            return new DataItem($response);
        })),
    validationExpression: 'data["hasNextPage"] == true'
);

$chainConfig->addLink($repeatConfig);
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

### Real API Pagination Example

{% capture description %}
Here's a more realistic example using actual HTTP requests with cursor-based pagination (common with modern APIs).
{% endcapture %}
{% capture code %}
```php
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;

// Prepare initial data with API URL
$initialData = new DataItem([
    'cursor' => null,
    'hasMore' => true,
]);

$repeatConfig = new ChainRepeatConfig(
    chainConfig: (new ChainConfig())
        // Make API call with cursor
        ->addLink(new SimpleHttpConfig(
            url: '@"https://api.example.com/items?cursor="~(data["cursor"] ?? "")',
            method: 'GET',
            responseIsJson: true,
            responseKey: 'apiResponse'
        ))
        // Extract pagination info from response
        ->addLink(new CallBackTransformerConfig(function(DataItemInterface $item) {
            $data = $item->getData();
            $response = $data['apiResponse'];
            
            return new DataItem([
                'items' => $response['data'],
                'cursor' => $response['pagination']['next_cursor'] ?? null,
                'hasMore' => !empty($response['pagination']['next_cursor']),
            ]);
        })),
    validationExpression: 'data["hasMore"] == true'
);
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

### Processing Paginated Results

{% capture description %}
After fetching all pages, we need to process the accumulated data. Use `SplitItemConfig` to split the items array
into individual DataItems for downstream processing.
{% endcapture %}
{% capture code %}
```php
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SplitItemConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$chainConfig
    ->addLink($repeatConfig)
    // Split the items array into individual items
    ->addLink(new SplitItemConfig(
        keys: ['items'],
        singleElement: false
    ))
    // Now each item is processed individually
    ->addLink(new CsvFileWriterConfig('all-items.csv'));
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

### Page Number Based Pagination

{% capture description %}
Some APIs use traditional page numbers instead of cursors. Here's how to handle that pattern:
{% endcapture %}
{% capture code %}
```php
$page = 1;
$pageSize = 100;

$repeatConfig = new ChainRepeatConfig(
    chainConfig: (new ChainConfig())
        ->addLink(new SimpleHttpConfig(
            url: '@"https://api.example.com/items?page="~data["page"]~"&limit="~data["pageSize"]',
            method: 'GET',
            responseIsJson: true,
            responseKey: 'apiResponse'
        ))
        ->addLink(new CallBackTransformerConfig(function(DataItemInterface $item) use (&$page, $pageSize) {
            $data = $item->getData();
            $response = $data['apiResponse'];
            $totalItems = $response['total'];
            
            $currentPage = $page++;
            $hasMore = ($currentPage * $pageSize) < $totalItems;
            
            return new DataItem([
                'items' => $response['items'],
                'page' => $page,
                'pageSize' => $pageSize,
                'hasMore' => $hasMore,
            ]);
        })),
    validationExpression: 'data["hasMore"] == true'
);
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

### Complete Example

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainRepeatConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SplitItemConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;

$chainConfig = new ChainConfig();

$page = 1;
$totalPages = 5;

// Create a repeat configuration for pagination
$repeatConfig = new ChainRepeatConfig(
    chainConfig: (new ChainConfig())
        ->addLink(new CallBackTransformerConfig(function(DataItemInterface $dataItem) use (&$page, $totalPages) {
            echo "Fetching page {$page}/{$totalPages}...\n";

            // Simulate API response with paginated data
            $items = [];
            for ($i = 1; $i <= 10; $i++) {
                $itemId = (($page - 1) * 10) + $i;
                $items[] = [
                    'id' => $itemId,
                    'name' => "Item {$itemId}",
                    'page' => $page,
                ];
            }

            $hasNextPage = $page < $totalPages;
            $page++;

            return new DataItem([
                'items' => $items,
                'hasNextPage' => $hasNextPage,
            ]);
        })),
    validationExpression: 'data["hasNextPage"] == true',
    allowAsynchronous: false
);

$chainConfig
    ->addLink($repeatConfig)
    ->addLink(new SplitItemConfig(keys: ['items']))
    ->addLink(new CsvFileWriterConfig('paginated-results.csv'));

// Create and execute the chain
$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem([])]),
    []
);
```
