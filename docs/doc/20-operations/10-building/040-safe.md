---
layout: base
title: PHP-ETL - Operations
subTitle: Building Blocks - Fail Safe
---

The `FailSafeConfig` operation handles exceptions within an ETL chain, making your pipelines resilient to transient errors. It wraps a chain in a "safe" block, catching specified exceptions and automatically retrying the operation. This is critical for production ETL processes that need to handle network issues, temporary API failures, or other recoverable errors gracefully.

**Key characteristics:**
- **Catches specified exceptions** and retries automatically
- **Limits retry attempts** to prevent infinite loops
- **Continues processing** other items if one fails
- **Re-throws unhandled exceptions** that aren't in the catch list
- Essential for production reliability and error resilience

## Configuration

Use `FailSafeConfig` with these parameters:

```php
use Oliverde8\Component\PhpEtl\OperationConfig\FailSafeConfig;
use Oliverde8\Component\PhpEtl\ChainConfig;

$failSafeConfig = new FailSafeConfig(
    chainConfig: $chainToProtect,           // ChainConfig to wrap
    exceptionsToCatch: [\Exception::class], // Array of exception classes to catch
    nbAttempts: 3                           // Number of attempts (default: 3)
);
```

**Parameters:**
- `chainConfig`: The `ChainConfig` to execute with error protection
- `exceptionsToCatch`: Array of exception class names to catch and retry
- `nbAttempts`: Total number of attempts (including first try). Default is 3

## Example: Basic API Error Handling

Retry failed API calls automatically:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\FailSafeConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$chainConfig = new ChainConfig();

// API call that might fail due to network issues
$apiChain = (new ChainConfig())
    ->addLink(new SimpleHttpConfig(
        url: 'https://api.example.com/data',
        method: 'GET',
        responseIsJson: true
    ));

// Wrap in FailSafe - retry up to 3 times on any exception
$chainConfig
    ->addLink(new FailSafeConfig(
        chainConfig: $apiChain,
        exceptionsToCatch: [\Exception::class],
        nbAttempts: 3
    ))
    ->addLink(new CsvFileWriterConfig('output.csv'));
```

## Example: Catching Specific Exceptions

Only retry certain types of errors:

```php
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\Exception\ServerException;

$apiChain = (new ChainConfig())
    ->addLink(new SimpleHttpConfig(
        url: 'https://api.example.com/users',
        method: 'GET',
        responseIsJson: true
    ));

// Only retry network and server errors, not client errors (4xx)
$failSafeConfig = new FailSafeConfig(
    chainConfig: $apiChain,
    exceptionsToCatch: [
        TransportException::class,  // Network errors
        ServerException::class,     // 5xx server errors
        \RuntimeException::class,   // Runtime issues
    ],
    nbAttempts: 3
);

$chainConfig->addLink($failSafeConfig);
```

**Note**: Validation errors (like 400 Bad Request) won't be caught, causing immediate failure - which is correct for non-retryable errors.

## Example: Transient Error Simulation

Handle transient errors that succeed after a few attempts:

```php
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;

$attempt = 0;

$failingChain = (new ChainConfig())
    ->addLink(new CallBackTransformerConfig(function(DataItem $item) use (&$attempt) {
        $attempt++;
        echo "Attempt {$attempt}\n";
        
        // Fail on first 2 attempts, succeed on 3rd
        if ($attempt < 3) {
            throw new RuntimeException('Transient error, please retry');
        }
        
        echo "Success!\n";
        return $item;
    }));

$chainConfig->addLink(new FailSafeConfig(
    chainConfig: $failingChain,
    exceptionsToCatch: [RuntimeException::class],
    nbAttempts: 5  // Allow up to 5 attempts
));
```

**Result**: Fails twice, succeeds on 3rd attempt, processing continues.

## Example: Continue Processing on Failure

Process a batch of items where some might fail, but others should continue:

```php
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;

$processingChain = (new ChainConfig())
    ->addLink(new CallBackTransformerConfig(function(DataItem $item) {
        $data = $item->getData();
        
        // Validate item
        if (empty($data['email'])) {
            throw new \InvalidArgumentException('Email is required');
        }
        
        // Process valid item
        processItem($data);
        return $item;
    }));

// Failed items are skipped, but processing continues with next items
$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink(new FailSafeConfig(
        chainConfig: $processingChain,
        exceptionsToCatch: [\InvalidArgumentException::class],
        nbAttempts: 1  // Don't retry validation errors
    ))
    ->addLink(new CsvFileWriterConfig('valid-items.csv'));
```

**Result**: Invalid items are skipped, valid items are processed and saved.

## Example: API Retry with Error Logging

Log failures while retrying:

```php
$failedItems = [];

$apiChain = (new ChainConfig())
    ->addLink(new CallBackTransformerConfig(function(DataItem $item) use (&$failedItems) {
        static $attempt = 0;
        $attempt++;
        
        try {
            // Make API call
            $result = makeApiCall($item->getData());
            return new DataItem($result);
        } catch (\Exception $e) {
            // Log the failure
            $failedItems[] = [
                'data' => $item->getData(),
                'error' => $e->getMessage(),
                'attempt' => $attempt,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            throw $e;  // Re-throw to trigger retry
        }
    }));

$chainConfig->addLink(new FailSafeConfig(
    chainConfig: $apiChain,
    exceptionsToCatch: [\Exception::class],
    nbAttempts: 3
));

// After processing, save failed items for review
file_put_contents('failed-items.json', json_encode($failedItems, JSON_PRETTY_PRINT));
```

## Example: Exponential Backoff Pattern

Add delays between retries for rate-limited APIs:

```php
$apiChain = (new ChainConfig())
    ->addLink(new CallBackTransformerConfig(function(DataItem $item) {
        static $attempt = 0;
        $attempt++;
        
        // Exponential backoff: 0s, 1s, 2s, 4s, 8s
        if ($attempt > 1) {
            $delay = pow(2, $attempt - 2);
            echo "Waiting {$delay} seconds before retry...\n";
            sleep($delay);
        }
        
        // Make API call
        return makeApiCall($item);
    }))
    ->addLink(new SimpleHttpConfig(
        url: 'https://api.example.com/data',
        method: 'POST',
        responseIsJson: true
    ));

$failSafeConfig = new FailSafeConfig(
    chainConfig: $apiChain,
    exceptionsToCatch: [\Exception::class],
    nbAttempts: 5
);
```

## Example: Database Operation with Retry

Handle database deadlocks or connection issues:

```php
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Exception\ConnectionException;

$dbChain = (new ChainConfig())
    ->addLink(new CallBackTransformerConfig(function(DataItem $item) use ($entityManager) {
        $data = $item->getData();
        
        // Database operation that might fail
        $entity = new Customer();
        $entity->setName($data['name']);
        $entity->setEmail($data['email']);
        
        $entityManager->persist($entity);
        $entityManager->flush();
        
        return $item;
    }));

$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink(new FailSafeConfig(
        chainConfig: $dbChain,
        exceptionsToCatch: [
            DeadlockException::class,
            ConnectionException::class,
        ],
        nbAttempts: 5
    ));
```

## Example: Multiple FailSafe Layers

Different retry strategies for different operations:

```php
$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new CsvExtractConfig())
    
    // First FailSafe: API call with 3 retries
    ->addLink(new FailSafeConfig(
        chainConfig: (new ChainConfig())
            ->addLink(new SimpleHttpConfig(
                url: 'https://api.example.com/enrich',
                method: 'POST',
                responseIsJson: true
            )),
        exceptionsToCatch: [\Exception::class],
        nbAttempts: 3
    ))
    
    // Second FailSafe: Database save with 5 retries
    ->addLink(new FailSafeConfig(
        chainConfig: (new ChainConfig())
            ->addLink(new CallBackTransformerConfig(function(DataItem $item) {
                saveToDatabase($item->getData());
                return $item;
            })),
        exceptionsToCatch: [DeadlockException::class],
        nbAttempts: 5
    ))
    
    ->addLink(new CsvFileWriterConfig('completed.csv'));
```

## Understanding Retry Behavior

**Retry Count:**

- `nbAttempts: 1` = No retries (runs once, fails if exception)
- `nbAttempts: 3` = Up to 2 retries (runs 3 times total)
- `nbAttempts: 5` = Up to 4 retries (runs 5 times total)

**Exception Handling:**

```php
exceptionsToCatch: [\Exception::class]       // Catches all exceptions
exceptionsToCatch: [RuntimeException::class]  // Only RuntimeException
exceptionsToCatch: [
    NetworkException::class,
    TimeoutException::class
]  // Multiple specific exceptions
```

**When Retry Stops:**

- Operation succeeds (no exception thrown)
- Maximum attempts reached
- Different exception type is thrown (not in catch list)

## Common Use Cases

- **API resilience**: Handle temporary API outages or rate limits
- **Network reliability**: Retry on connection timeouts or DNS failures
- **Database operations**: Handle deadlocks and connection pool exhaustion
- **Batch processing**: Skip invalid items while processing valid ones
