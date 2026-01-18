---
layout: base
title: PHP-ETL - Cook Books
subTitle: Error Handling with FailSafe
width: large
---

### Error Handling with FailSafe

In production ETL pipelines, errors are inevitable. Network issues, temporary API failures, database locks, or
invalid data can all cause operations to fail. The `FailSafeConfig` operation provides error handling
and automatic retry logic to make your ETL chains resilient.

## How FailSafe Works

The `FailSafe` operation wraps a chain of operations and:
- **Catches specified exceptions** and retries the operation
- **Limits retry attempts** to prevent infinite loops
- **Continues processing** other items even if one fails
- **Logs failures** for monitoring and debugging

This is critical for production use where you want graceful degradation instead of complete failure.

### Basic Error Handling

{% capture description %}
Let's start with a simple example that retries a failing operation. This simulates a transient error (like a 
temporary network issue) that succeeds after a few retries.
{% endcapture %}
{% capture code %}
```php
use Oliverde8\Component\PhpEtl\OperationConfig\FailSafeConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;

// Chain that might fail
$failingChain = (new ChainConfig())
    ->addLink(new CallBackTransformerConfig(function(DataItem $item) {
        static $attempt = 0;
        $attempt++;
        
        if ($attempt < 3) {
            throw new RuntimeException('Transient error, please retry');
        }
        
        // Success on 3rd attempt
        return $item;
    }));

// Wrap it in FailSafe
$failSafeConfig = new FailSafeConfig(
    chainConfig: $failingChain,
    exceptionsToCatch: [RuntimeException::class],
    nbAttempts: 5
);

$chainConfig->addLink($failSafeConfig);
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

### API Retry Pattern

{% capture description %}
A common use case is retrying failed API calls. This handles temporary network issues, rate limits, or server errors.
{% endcapture %}
{% capture code %}
```php
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;

// API call that might fail
$apiChain = (new ChainConfig())
    ->addLink(new SimpleHttpConfig(
        url: 'https://api.example.com/data',
        method: 'GET',
        responseIsJson: true
    ));

// Retry up to 3 times on any exception
$failSafeConfig = new FailSafeConfig(
    chainConfig: $apiChain,
    exceptionsToCatch: [\Exception::class],  // Catch all exceptions
    nbAttempts: 3
);

$chainConfig->addLink($failSafeConfig);
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

### Catching Specific Exceptions

{% capture description %}
You can specify which exceptions to catch and retry. This allows different handling for different types of errors.
For example, retry network errors but not validation errors.
{% endcapture %}
{% capture code %}
```php
use Symfony\Component\HttpClient\Exception\TransportException;

// Only retry on network/transport errors
$failSafeConfig = new FailSafeConfig(
    chainConfig: $apiChain,
    exceptionsToCatch: [
        TransportException::class,      // Network errors
        \RuntimeException::class,        // Runtime issues
    ],
    nbAttempts: 3
);

// ValidationException would NOT be caught and would fail immediately
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

### Continue Processing on Failure

{% capture description %}
One of the most powerful features of FailSafe is that it allows the chain to continue processing other items 
even if one fails. This is critical for batch processing where you don't want a single bad record to stop 
the entire job.
{% endcapture %}
{% capture code %}
```php
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$processingChain = (new ChainConfig())
    ->addLink(new CallBackTransformerConfig(function(DataItem $item) {
        $data = $item->getData();
        
        // Some items might fail validation or processing
        if (empty($data['email'])) {
            throw new \InvalidArgumentException('Email is required');
        }
        
        return $item;
    }))
    ->addLink(new CsvFileWriterConfig('valid-records.csv'));

// Failed items are skipped, but processing continues
$failSafeConfig = new FailSafeConfig(
    chainConfig: $processingChain,
    exceptionsToCatch: [\InvalidArgumentException::class],
    nbAttempts: 1  // Don't retry validation errors
);

$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink($failSafeConfig);
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

### Logging Failed Items

{% capture description %}
For production systems, you'll want to log which items failed so you can investigate or reprocess them later.
{% endcapture %}
{% capture code %}
```php
$failedItems = [];

$processingChain = (new ChainConfig())
    ->addLink(new CallBackTransformerConfig(function(DataItem $item) use (&$failedItems) {
        $data = $item->getData();
        
        try {
            // Your processing logic
            processData($data);
            return $item;
        } catch (\Exception $e) {
            // Log the failure
            $failedItems[] = [
                'data' => $data,
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            throw $e;  // Re-throw to trigger FailSafe
        }
    }));

$failSafeConfig = new FailSafeConfig(
    chainConfig: $processingChain,
    exceptionsToCatch: [\Exception::class],
    nbAttempts: 3
);

// After processing, write failed items to a separate file
file_put_contents('failed-items.json', json_encode($failedItems, JSON_PRETTY_PRINT));
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

### Exponential Backoff for Retries

{% capture description %}
For API calls, you often want to wait between retries with increasing delays (exponential backoff). 
This is especially important when dealing with rate limits.
{% endcapture %}
{% capture code %}
```php
$apiChain = (new ChainConfig())
    ->addLink(new CallBackTransformerConfig(function(DataItem $item) {
        static $attempt = 0;
        $attempt++;
        
        // Exponential backoff: 1s, 2s, 4s, 8s
        if ($attempt > 1) {
            $delay = pow(2, $attempt - 2);
            echo "Waiting {$delay} seconds before retry...\n";
            sleep($delay);
        }
        
        // Make API call
        // ...
        
        return $item;
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
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

### Complete Example

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\FailSafeConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;

$chainConfig = new ChainConfig();

// Read customer data
$chainConfig->addLink(new CsvExtractConfig());

// Enrich with API data (with retry logic)
$apiEnrichmentChain = (new ChainConfig())
    ->addLink(new CallBackTransformerConfig(function(DataItem $item) {
        static $attempt = 0;
        $attempt++;
        
        $data = $item->getData();
        echo "Fetching data for customer {$data['id']} (attempt {$attempt})...\n";
        
        // Simulate occasional failures
        if (rand(1, 10) > 7 && $attempt < 3) {
            throw new RuntimeException('API timeout');
        }
        
        return $item;
    }))
    ->addLink(new SimpleHttpConfig(
        url: '@"https://api.example.com/customer/"~data["id"]',
        method: 'GET',
        responseIsJson: true,
        responseKey: 'apiData'
    ));

$chainConfig->addLink(new FailSafeConfig(
    chainConfig: $apiEnrichmentChain,
    exceptionsToCatch: [RuntimeException::class],
    nbAttempts: 3
));

// Write enriched data
$chainConfig->addLink(new CsvFileWriterConfig('enriched-customers.csv'));

// Create and execute the chain
$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem(['file' => 'customers.csv'])]),
    []
);
```

### Best Practices

**1. Choose Appropriate Retry Counts**

- Transient errors (network): 3-5 attempts
- Rate limits: 5-10 attempts with backoff
- Validation errors: 1 attempt (don't retry)

**2. Catch Specific Exceptions**

- Only catch exceptions you know can be retried
- Let validation errors fail fast
- Different retry strategies for different error types


**3. Add Delays Between Retries**

- Use exponential backoff for APIs
- Respect rate limits
- Don't overwhelm failing services

### Common Use Cases

- **API Integration**: Retry failed HTTP requests due to network issues
- **Database Operations**: Retry on deadlocks or connection issues
- **File Processing**: Handle temporary file system errors
- **Batch Processing**: Skip invalid records while continuing with valid ones
- **External Services**: Handle service unavailability gracefully
