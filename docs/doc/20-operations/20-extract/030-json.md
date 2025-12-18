---
layout: base
title: PHP-ETL - Operations
subTitle: Extract - JSON File
---

The `JsonExtractConfig` operation reads a JSON file and outputs `DataItem`s for each element in the JSON structure. It's designed to handle both single JSON objects and arrays of objects, making it ideal for API responses, configuration files, and data exports in JSON format.

---

## Configuration

Use `JsonExtractConfig` with an optional fileKey parameter:

```php
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\JsonExtractConfig;

$jsonConfig = new JsonExtractConfig(
    fileKey: 'file'  // Optional: key containing file path (default: null, reads from data directly)
);
```

**Parameters:**
- `fileKey`: The key in the input data that contains the JSON file path. If null, the input data itself is treated as the file path (supports nested keys like `'data/file'`)

**Input Data:**
- When `fileKey` is null: DataItem containing the file path as a string
- When `fileKey` is set: DataItem containing an array with the file path at the specified key

---

## Example: Basic JSON Reading

Read a JSON file containing an array of objects:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\JsonExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;

$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new JsonExtractConfig())
    ->addLink((new RuleTransformConfig(false))
        ->addColumn('id', [['get' => ['field' => 'id']]])
        ->addColumn('name', [['get' => ['field' => 'name']]])
        ->addColumn('email', [['get' => ['field' => 'email']]])
    )
    ->addLink(new CsvFileWriterConfig('customers.csv'));

$chainProcessor = $chainBuilder->createChain($chainConfig);

// Input: file path as string
$chainProcessor->process(
    new ArrayIterator([new DataItem('data/customers.json')]),
    []
);
```

**Input JSON file (data/customers.json):**
```json
[
  {"id": 1, "name": "John Doe", "email": "john@example.com"},
  {"id": 2, "name": "Jane Smith", "email": "jane@example.com"},
  {"id": 3, "name": "Bob Wilson", "email": "bob@example.com"}
]
```

## Example: With FileKey

When the file path is in a specific key:

```php
$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new JsonExtractConfig(fileKey: 'file'))
    ->addLink((new RuleTransformConfig(false))
        ->addColumn('product_id', [['get' => ['field' => 'productId']]])
        ->addColumn('sku', [['get' => ['field' => 'sku']]])
        ->addColumn('price', [['get' => ['field' => 'price']]])
    )
    ->addLink(new CsvFileWriterConfig('products.csv'));

// Input: DataItem with 'file' key
$chainProcessor->process(
    new ArrayIterator([new DataItem(['file' => 'data/products.json'])]),
    []
);
```

## Example: Nested JSON Structure

Extract data from nested JSON objects:

```php
$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new JsonExtractConfig())
    ->addLink((new RuleTransformConfig(false))
        ->addColumn('order_id', [['get' => ['field' => 'id']]])
        ->addColumn('customer_name', [['get' => ['field' => ['customer', 'name']]]])
        ->addColumn('customer_email', [['get' => ['field' => ['customer', 'email']]]])
        ->addColumn('total', [['get' => ['field' => 'total']]])
        ->addColumn('status', [['get' => ['field' => 'status']]])
    )
    ->addLink(new CsvFileWriterConfig('orders.csv'));

$chainProcessor->process(
    new ArrayIterator([new DataItem('data/orders.json')]),
    []
);
```

**Input JSON with nested structure:**
```json
[
  {
    "id": 1001,
    "customer": {
      "name": "John Doe",
      "email": "john@example.com"
    },
    "total": 150.00,
    "status": "completed"
  }
]
```

## Example: Processing API Response

Convert API JSON response to CSV:

```php
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;

$chainConfig = new ChainConfig();

$chainConfig
    // Fetch data from API
    ->addLink(new SimpleHttpConfig(
        url: 'https://api.example.com/users',
        method: 'GET',
        responseIsJson: true,
        responseKey: 'api_response'
    ))
    // Save JSON response to file temporarily
    ->addLink(new CallBackTransformerConfig(function(DataItem $item) {
        $data = $item->getData();
        $jsonFile = 'temp/api-response.json';
        file_put_contents($jsonFile, json_encode($data['api_response']));
        return new DataItem($jsonFile);
    }))
    // Extract JSON
    ->addLink(new JsonExtractConfig())
    // Transform
    ->addLink((new RuleTransformConfig(false))
        ->addColumn('user_id', [['get' => ['field' => 'id']]])
        ->addColumn('username', [['get' => ['field' => 'username']]])
    )
    ->addLink(new CsvFileWriterConfig('users.csv'));

$chainProcessor->process(
    new ArrayIterator([new DataItem([])]),
    []
);
```

## Example: With Dynamic Columns

Use context to create dynamic column names:

```php
$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new JsonExtractConfig())
    ->addLink((new RuleTransformConfig(false))
        ->addColumn('productId', [['get' => ['field' => 'productId']]])
        ->addColumn('sku', [['get' => ['field' => 'sku']]])
        // Dynamic column for each locale
        ->addColumn('name-{@context/locales}', [['get' => ['field' => ['name', '@context/locales']]]])
    )
    ->addLink(new CsvFileWriterConfig('products.csv'));

$chainProcessor->process(
    new ArrayIterator([new DataItem('data/products.json')]),
    ['locales' => ['fr_FR', 'en_US']]
);
```

**Input JSON:**
```json
[
  {
    "productId": 1,
    "sku": "PROD-001",
    "name": {
      "fr_FR": "Produit Un",
      "en_US": "Product One"
    }
  }
]
```

**Output CSV:**
```csv
productId,sku,name-fr_FR,name-en_US
1,PROD-001,Produit Un,Product One
```

## Example: Flattening Complex JSON

Convert complex nested JSON to flat CSV structure:

```php
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SplitItemConfig;

$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new JsonExtractConfig())
    // Split items array into separate DataItems
    ->addLink(new SplitItemConfig(keys: ['items']))
    ->addLink((new RuleTransformConfig(false))
        ->addColumn('order_id', [['get' => ['field' => 'orderId']]])
        ->addColumn('item_name', [['get' => ['field' => 'data', 'name']]])
        ->addColumn('item_price', [['get' => ['field' => 'data', 'price']]])
        ->addColumn('quantity', [['get' => ['field' => 'data', 'quantity']]])
    )
    ->addLink(new CsvFileWriterConfig('order-items.csv'));

$chainProcessor->process(
    new ArrayIterator([new DataItem('data/orders.json')]),
    []
);
```

**Input JSON:**
```json
[
  {
    "orderId": 1,
    "items": [
      {"name": "Widget", "price": 10.00, "quantity": 2},
      {"name": "Gadget", "price": 15.00, "quantity": 1}
    ]
  }
]
```

**Output:** One row per item (2 rows from 1 order)

## Example: With Filtering

Extract and filter JSON data:

```php
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;

$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new JsonExtractConfig())
    // Only process active products
    ->addLink(new FilterDataConfig('@data["status"] == "active"'))
    ->addLink((new RuleTransformConfig(false))
        ->addColumn('id', [['get' => ['field' => 'id']]])
        ->addColumn('name', [['get' => ['field' => 'name']]])
        ->addColumn('price', [['get' => ['field' => 'price']]])
    )
    ->addLink(new CsvFileWriterConfig('active-products.csv'));

$chainProcessor->process(
    new ArrayIterator([new DataItem('data/products.json')]),
    []
);
```

## Example: Multiple JSON Files

Process multiple JSON files in one chain:

```php
$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new JsonExtractConfig(fileKey: 'file'))
    ->addLink((new RuleTransformConfig(false))
        ->addColumn('id', [['get' => ['field' => 'id']]])
        ->addColumn('data', [['get' => ['field' => 'data']]])
        ->addColumn('source_file', [['get' => ['field' => 'file']]])
    )
    ->addLink(new CsvFileWriterConfig('combined.csv'));

// Process multiple files
$files = [
    new DataItem(['file' => 'data1.json']),
    new DataItem(['file' => 'data2.json']),
    new DataItem(['file' => 'data3.json']),
];

$chainProcessor->process(new ArrayIterator($files), []);
```

## Example: With External File Finder

Import JSON files from remote location:

```php
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\ExternalFileFinderConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\ExternalFileProcessorConfig;

$chainConfig = new ChainConfig();

$chainConfig
    // Find JSON files on remote filesystem
    ->addLink(new ExternalFileFinderConfig(directory: '/remote/exports'))
    // Copy file locally
    ->addLink(new ExternalFileProcessorConfig())
    // Extract JSON data
    ->addLink(new JsonExtractConfig())
    // Transform data
    ->addLink((new RuleTransformConfig(false))
        ->addColumn('id', [['get' => ['field' => 'id']]])
        ->addColumn('status', [['get' => ['field' => 'status']]])
    )
    // Save results
    ->addLink(new CsvFileWriterConfig('imported-data.csv'))
    // Clean up local file
    ->addLink(new ExternalFileProcessorConfig());

$chainProcessor->process(
    new ArrayIterator([new DataItem('/^export_[0-9]{8}\.json$/')]),
    []
);
```

## Example: Error Handling for Malformed JSON

Handle invalid JSON files gracefully:

```php
use Oliverde8\Component\PhpEtl\OperationConfig\FailSafeConfig;

$jsonChain = (new ChainConfig())
    ->addLink(new JsonExtractConfig())
    ->addLink((new RuleTransformConfig(false))
        ->addColumn('id', [['get' => ['field' => 'id']]])
        ->addColumn('name', [['get' => ['field' => 'name']]])
    );

$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new FailSafeConfig(
        chainConfig: $jsonChain,
        exceptionsToCatch: [\JsonException::class, \Exception::class],
        nbAttempts: 1  // Don't retry malformed JSON
    ))
    ->addLink(new CsvFileWriterConfig('valid-records.csv'));

$chainProcessor->process(
    new ArrayIterator([new DataItem('data.json')]),
    []
);
```
