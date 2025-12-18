---
layout: base
title: PHP-ETL - Operations
subTitle: Building Blocks - Chain Repeat
---

The `ChainRepeatConfig` operation executes a chain of operations repeatedly until a specified condition is met. 
This is the perfect solution for paginated APIs, iterative data processing, or any scenario where you need to loop until a condition becomes false.

**Key characteristics:**
- Executes a sub-chain **repeatedly in a loop**
- Continues while validation expression evaluates to **true**
- Stops when validation expression returns **false**
- Each iteration can modify data for the next iteration
- Supports asynchronous processing within the loop

## Configuration

Use `ChainRepeatConfig` with these parameters:

```php
use Oliverde8\Component\PhpEtl\OperationConfig\ChainRepeatConfig;
use Oliverde8\Component\PhpEtl\ChainConfig;

$repeatConfig = new ChainRepeatConfig(
    chainConfig: $chainToRepeat,           // ChainConfig to execute repeatedly
    validationExpression: 'expression',     // Symfony expression - loop while true
    allowAsynchronous: false                // Optional: allow async operations (default: false)
);
```

**Parameters:**
- `chainConfig`: A `ChainConfig` containing operations to execute in each iteration
- `validationExpression`: A Symfony Expression Language expression that must return boolean. Loop continues while `true`
- `allowAsynchronous`: Whether operations within the loop can execute asynchronously

## Example: Basic API Pagination

Fetch all pages from a paginated API:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainRepeatConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SplitItemConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;

$chainConfig = new ChainConfig();

$page = 1;

$repeatConfig = new ChainRepeatConfig(
    chainConfig: (new ChainConfig())
        ->addLink(new CallBackTransformerConfig(function(DataItemInterface $dataItem) use (&$page) {
            echo "Fetching page {$page}...\n";
            
            $totalPages = 5;
            
            // Simulate API call returning paginated data
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
    ->addLink(new SplitItemConfig(keys: ['items']))  // Extract items array
    ->addLink(new CsvFileWriterConfig('all-items.csv'));

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem([])]),
    []
);
```

**Result**: Fetches pages 1-5, extracts all items, writes to CSV.

## Example: Cursor-Based API Pagination

Modern APIs often use cursor-based pagination instead of page numbers:

```php
$repeatConfig = new ChainRepeatConfig(
    chainConfig: (new ChainConfig())
        // Make API request with cursor
        ->addLink(new SimpleHttpConfig(
            url: '@"https://api.example.com/items?cursor="~(data["cursor"] ?? "")',
            method: 'GET',
            responseIsJson: true,
            responseKey: 'response'
        ))
        // Process response and extract next cursor
        ->addLink(new CallBackTransformerConfig(function(DataItemInterface $item) {
            $data = $item->getData();
            $response = $data['response'];
            
            return new DataItem([
                'items' => $response['data'],
                'cursor' => $response['pagination']['next_cursor'] ?? null,
                'hasMore' => !empty($response['pagination']['next_cursor']),
            ]);
        }))
        ->addLink(new SplitItemConfig(keys: ['items'])),
    validationExpression: 'data["hasMore"] == true'
);

$chainConfig
    ->addLink($repeatConfig)
    ->addLink(new CsvFileWriterConfig('api-data.csv'));
```

## Example: Page Number Pagination

APIs that use page numbers with a total page count:

```php
$repeatConfig = new ChainRepeatConfig(
    chainConfig: (new ChainConfig())
        ->addLink(new SimpleHttpConfig(
            url: '@"https://api.example.com/users?page="~data["currentPage"]',
            method: 'GET',
            responseIsJson: true,
            responseKey: 'response'
        ))
        ->addLink(new CallBackTransformerConfig(function(DataItemInterface $item) {
            $data = $item->getData();
            $response = $data['response'];
            
            return new DataItem([
                'items' => $response['results'],
                'currentPage' => $data['currentPage'] + 1,
                'totalPages' => $response['total_pages'],
            ]);
        }))
        ->addLink(new SplitItemConfig(keys: ['items'])),
    validationExpression: 'data["currentPage"] <= data["totalPages"]'
);

// Start with page 1
$chainProcessor->process(
    new ArrayIterator([new DataItem(['currentPage' => 1, 'totalPages' => 1])]),
    []
);
```

## Example: Offset-Based Pagination

APIs using offset/limit pattern:

```php
$repeatConfig = new ChainRepeatConfig(
    chainConfig: (new ChainConfig())
        ->addLink(new SimpleHttpConfig(
            url: '@"https://api.example.com/records?offset="~data["offset"]~"&limit=100"',
            method: 'GET',
            responseIsJson: true,
            responseKey: 'response'
        ))
        ->addLink(new CallBackTransformerConfig(function(DataItemInterface $item) {
            $data = $item->getData();
            $response = $data['response'];
            $fetchedCount = count($response['records']);
            
            return new DataItem([
                'items' => $response['records'],
                'offset' => $data['offset'] + $fetchedCount,
                'total' => $response['total'],
                'hasMore' => $data['offset'] + $fetchedCount < $response['total'],
            ]);
        }))
        ->addLink(new SplitItemConfig(keys: ['items'])),
    validationExpression: 'data["hasMore"] == true'
);

// Start at offset 0
$chainProcessor->process(
    new ArrayIterator([new DataItem(['offset' => 0])]),
    []
);
```

## Example: Retry Until Success

Use ChainRepeat for retry logic (though FailSafeConfig is usually better for this):

```php
$attempts = 0;
$maxAttempts = 3;

$repeatConfig = new ChainRepeatConfig(
    chainConfig: (new ChainConfig())
        ->addLink(new CallBackTransformerConfig(function(DataItemInterface $item) use (&$attempts, $maxAttempts) {
            $attempts++;
            echo "Attempt {$attempts}/{$maxAttempts}\n";
            
            // Simulate operation that might fail
            $success = rand(1, 10) > 7; // 30% success rate
            
            if ($success) {
                echo "Success!\n";
            }
            
            return new DataItem([
                'success' => $success,
                'shouldRetry' => !$success && $attempts < $maxAttempts,
            ]);
        })),
    validationExpression: 'data["shouldRetry"] == true'
);
```

## Example: With Error Handling

Combine ChainRepeat with FailSafe for robust pagination:

```php
use Oliverde8\Component\PhpEtl\OperationConfig\FailSafeConfig;

$repeatConfig = new ChainRepeatConfig(
    chainConfig: (new ChainConfig())
        ->addLink(new FailSafeConfig(
            chainConfig: (new ChainConfig())
                ->addLink(new SimpleHttpConfig(
                    url: '@"https://api.example.com/data?page="~data["page"]',
                    method: 'GET',
                    responseIsJson: true
                )),
            exceptionsToCatch: [\Exception::class],
            nbAttempts: 3
        ))
        ->addLink(new CallBackTransformerConfig(function(DataItemInterface $item) {
            $data = $item->getData();
            
            return new DataItem([
                'items' => $data['results'] ?? [],
                'page' => $data['page'] + 1,
                'hasMore' => !empty($data['next']),
            ]);
        }))
        ->addLink(new SplitItemConfig(keys: ['items'])),
    validationExpression: 'data["hasMore"] == true'
);
```

## Understanding the Validation Expression

The validation expression is evaluated **before each iteration**. The loop continues while the expression returns `true`:

```php
// Loop while there are more pages
validationExpression: 'data["hasMore"] == true'

// Loop while page number is less than total
validationExpression: 'data["page"] < data["totalPages"]'

// Loop while cursor exists
validationExpression: 'data["cursor"] != null'

// Complex condition
validationExpression: 'data["hasMore"] == true and data["errorCount"] < 5'

// Using context
validationExpression: 'context["shouldContinue"] == true'
```

## Asynchronous Processing

Set `allowAsynchronous: true` to allow operations within the repeat chain to execute asynchronously:

```php
$repeatConfig = new ChainRepeatConfig(
    chainConfig: $paginationChain,
    validationExpression: 'data["hasMore"] == true',
    allowAsynchronous: true  // Enable async processing
);
```

## Common Use Cases

- **API Pagination**: Fetch all pages from paginated REST APIs
- **Cursor Navigation**: Process cursor-based API responses
- **Retry Logic**: Retry operations until success (use FailSafe instead when possible)
- **Polling**: Poll an endpoint until data is available
- **Queue Processing**: Process messages from a queue until empty
