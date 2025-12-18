---
layout: base
title: PHP-ETL - Cook Books
subTitle: ChainMerge - Combining Multiple Data Sources
width: large
---

### ChainMerge - Combining Multiple Data Sources

The `ChainMergeConfig` operation allows you to process a single data item through multiple parallel chains and 
combine all the results. This is powerful for enriching data from multiple sources, creating different views 
of the same data, or denormalizing complex datasets.

## How ChainMerge Works

ChainMerge takes one input item and:
- **Processes it through multiple chains in parallel**
- **Each chain can transform the data differently**
- **All results are returned to the next step** in the main chain
- **Order is preserved** - items from each branch are returned in order

⚠️ **Important**: If branches don't filter items, the same item will appear multiple times in the output. 
This is by design and allows for powerful data splitting and denormalization patterns.

### Basic Data Enrichment

{% capture description %}
The simplest use case is to create different views of the same data. For example, extracting different 
fields from a customer record into separate output items.
{% endcapture %}
{% capture code %}
```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainMergeConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink((new ChainMergeConfig())
        // Branch 1: Extract customer contact info
        ->addMerge((new ChainConfig())
            ->addLink((new RuleTransformConfig(false))
                ->addColumn('customer_id', [['get' => ['field' => 'ID']]])
                ->addColumn('full_name', [
                    ['implode' => [
                        'values' => [
                            [['get' => ['field' => 'FirstName']]],
                            [['get' => ['field' => 'LastName']]],
                        ],
                        'with' => ' ',
                    ]]
                ])
                ->addColumn('email', [['get' => ['field' => 'Email']]])
            )
        )
        // Branch 2: Extract customer subscription status
        ->addMerge((new ChainConfig())
            ->addLink((new RuleTransformConfig(false))
                ->addColumn('customer_id', [['get' => ['field' => 'ID']]])
                ->addColumn('status', [['get' => ['field' => 'subscribed']]])
                ->addColumn('subscription_date', [['get' => ['field' => 'created_at']]])
            )
        )
    )
    ->addLink(new CsvFileWriterConfig('enriched-data.csv'));
```

**Result**: Each customer record becomes 2 items - one with contact info, one with subscription info.
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

### Combining Data from Multiple APIs

{% capture description %}
A common pattern is enriching data by calling multiple APIs in parallel. Each branch fetches different 
information about the same entity.
{% endcapture %}
{% capture code %}
```php
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;

$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new CsvExtractConfig())  // Read customer IDs
    ->addLink((new ChainMergeConfig())
        // Branch 1: Get customer profile from API
        ->addMerge((new ChainConfig())
            ->addLink(new SimpleHttpConfig(
                url: '@"https://api.example.com/customers/"~data["customer_id"]',
                method: 'GET',
                responseIsJson: true,
                responseKey: 'profile'
            ))
        )
        // Branch 2: Get customer orders from API
        ->addMerge((new ChainConfig())
            ->addLink(new SimpleHttpConfig(
                url: '@"https://api.example.com/orders?customer="~data["customer_id"]',
                method: 'GET',
                responseIsJson: true,
                responseKey: 'orders'
            ))
        )
        // Branch 3: Get customer preferences from API
        ->addMerge((new ChainConfig())
            ->addLink(new SimpleHttpConfig(
                url: '@"https://api.example.com/preferences/"~data["customer_id"]',
                method: 'GET',
                responseIsJson: true,
                responseKey: 'preferences'
            ))
        )
    )
    ->addLink(new CsvFileWriterConfig('customer-full-data.csv'));
```

**Result**: Each customer becomes 3 items containing profile, orders, and preferences data.
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

### Conditional Data Routing

{% capture description %}
You can use filters in branches to route data to different outputs based on conditions. Combined with 
ChainMerge, this allows sophisticated data splitting.
{% endcapture %}
{% capture code %}
```php
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;

$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink((new ChainMergeConfig())
        // Branch 1: High-value customers (> $1000)
        ->addMerge((new ChainConfig())
            ->addLink(new FilterDataConfig('@data["total_spent"] > 1000'))
            ->addLink((new RuleTransformConfig(false))
                ->addColumn('customer_id', [['get' => ['field' => 'ID']]])
                ->addColumn('segment', [['const' => 'premium']])
                ->addColumn('total_spent', [['get' => ['field' => 'total_spent']]])
            )
            ->addLink(new CsvFileWriterConfig('premium-customers.csv'))
        )
        // Branch 2: Regular customers
        ->addMerge((new ChainConfig())
            ->addLink(new FilterDataConfig('@data["total_spent"] <= 1000'))
            ->addLink((new RuleTransformConfig(false))
                ->addColumn('customer_id', [['get' => ['field' => 'ID']]])
                ->addColumn('segment', [['const' => 'regular']])
                ->addColumn('total_spent', [['get' => ['field' => 'total_spent']]])
            )
            ->addLink(new CsvFileWriterConfig('regular-customers.csv'))
        )
        // Branch 3: All customers to main file
        ->addMerge((new ChainConfig())
            ->addLink((new RuleTransformConfig(false))
                ->addColumn('customer_id', [['get' => ['field' => 'ID']]])
                ->addColumn('name', [['get' => ['field' => 'name']]])
            )
        )
    )
    ->addLink(new CsvFileWriterConfig('all-customers.csv'));
```

**Result**: Premium and regular customers go to separate files, and all customers go to a main file.
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

### Denormalizing Hierarchical Data

{% capture description %}
ChainMerge is excellent for denormalizing complex JSON or hierarchical data into flat structures. 
For example, converting a product with multiple variants into separate rows.
{% endcapture %}
{% capture code %}
```php
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\JsonExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SplitItemConfig;

// Input: {"product_id": 123, "name": "T-Shirt", "variants": ["Small", "Medium", "Large"]}

$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new JsonExtractConfig())
    ->addLink((new ChainMergeConfig())
        // Branch 1: Create row for product base info
        ->addMerge((new ChainConfig())
            ->addLink((new RuleTransformConfig(false))
                ->addColumn('product_id', [['get' => ['field' => 'product_id']]])
                ->addColumn('name', [['get' => ['field' => 'name']]])
                ->addColumn('type', [['const' => 'base']])
            )
        )
        // Branch 2: Create row for each variant
        ->addMerge((new ChainConfig())
            ->addLink(new SplitItemConfig(path: 'variants'))
            ->addLink((new RuleTransformConfig(false))
                ->addColumn('product_id', [['get' => ['field' => 'product_id']]])
                ->addColumn('name', [['get' => ['field' => 'name']]])
                ->addColumn('variant', [['get' => ['field' => 'data']]])  // Split item data
                ->addColumn('type', [['const' => 'variant']])
            )
        )
    )
    ->addLink(new CsvFileWriterConfig('products-denormalized.csv'));
```

**Result**: One base product row + one row for each variant (4 rows total).
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

### Database and API Combination

{% capture description %}
Combine data from a database with real-time API enrichment. This is useful when you have master data 
in a database but need current information from external services.
{% endcapture %}
{% capture code %}
```php
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;

$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new CsvExtractConfig())  // Customer IDs from database export
    ->addLink((new ChainMergeConfig())
        // Branch 1: Local customer data (fast)
        ->addMerge((new ChainConfig())
            ->addLink(new CallBackTransformerConfig(function(DataItem $item) use ($pdo) {
                $id = $item->getData()['customer_id'];
                $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
                $stmt->execute([$id]);
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return new DataItem(array_merge(['source' => 'database'], $data));
            }))
        )
        // Branch 2: Real-time credit score from external API (slower)
        ->addMerge((new ChainConfig())
            ->addLink(new SimpleHttpConfig(
                url: '@"https://credit-api.example.com/score/"~data["customer_id"]',
                method: 'GET',
                responseIsJson: true
            ))
            ->addLink((new RuleTransformConfig(false))
                ->addColumn('customer_id', [['get' => ['field' => 'customer_id']]])
                ->addColumn('credit_score', [['get' => ['field' => 'score']]])
                ->addColumn('source', [['const' => 'credit_api']])
            )
        )
    )
    ->addLink(new CsvFileWriterConfig('customer-enriched.csv'));
```

**Result**: Each customer gets 2 rows - one with database data, one with API credit score.
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

### Error Isolation per Branch

{% capture description %}
Wrap each branch in a FailSafe to handle errors independently. If one data source fails, others can 
still succeed.
{% endcapture %}
{% capture code %}
```php
use Oliverde8\Component\PhpEtl\OperationConfig\FailSafeConfig;

$chainConfig = new ChainConfig();

$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink((new ChainMergeConfig())
        // Branch 1: Critical API (must succeed)
        ->addMerge((new ChainConfig())
            ->addLink(new SimpleHttpConfig(
                url: '@"https://api1.example.com/data/"~data["id"]',
                method: 'GET',
                responseIsJson: true,
                responseKey: 'api1_data'
            ))
        )
        // Branch 2: Optional API (can fail gracefully)
        ->addMerge((new FailSafeConfig(
            chainConfig: (new ChainConfig())
                ->addLink(new SimpleHttpConfig(
                    url: '@"https://api2.example.com/data/"~data["id"]',
                    method: 'GET',
                    responseIsJson: true,
                    responseKey: 'api2_data'
                )),
            exceptionsToCatch: [\Exception::class],
            nbAttempts: 2
        )))
        // Branch 3: Another optional API
        ->addMerge((new FailSafeConfig(
            chainConfig: (new ChainConfig())
                ->addLink(new SimpleHttpConfig(
                    url: '@"https://api3.example.com/data/"~data["id"]',
                    method: 'GET',
                    responseIsJson: true,
                    responseKey: 'api3_data'
                )),
            exceptionsToCatch: [\Exception::class],
            nbAttempts: 2
        )))
    )
    ->addLink(new CsvFileWriterConfig('merged-data.csv'));
```

**Result**: If API2 or API3 fail, you still get data from API1 and the working APIs.
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

### Complete Example: Multi-Source Customer Enrichment

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainMergeConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\FailSafeConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

$chainConfig = new ChainConfig();

// Extract customer IDs
$chainConfig->addLink(new CsvExtractConfig());

// Merge data from multiple sources
$chainConfig->addLink((new ChainMergeConfig())
    
    // Branch 1: Basic customer info (always include)
    ->addMerge((new ChainConfig())
        ->addLink((new RuleTransformConfig(false))
            ->addColumn('customer_id', [['get' => ['field' => 'ID']]])
            ->addColumn('full_name', [
                ['implode' => [
                    'values' => [
                        [['get' => ['field' => 'FirstName']]],
                        [['get' => ['field' => 'LastName']]],
                    ],
                    'with' => ' ',
                ]]
            ])
            ->addColumn('email', [['get' => ['field' => 'Email']]])
            ->addColumn('data_source', [['const' => 'basic_info']])
        )
    )
    
    // Branch 2: Subscription status (with retry)
    ->addMerge(new FailSafeConfig(
        chainConfig: (new ChainConfig())
            ->addLink(new SimpleHttpConfig(
                url: '@"https://api.example.com/subscriptions/"~data["ID"]',
                method: 'GET',
                responseIsJson: true
            ))
            ->addLink((new RuleTransformConfig(false))
                ->addColumn('customer_id', [['get' => ['field' => 'ID']]])
                ->addColumn('subscription_tier', [['get' => ['field' => 'tier']]])
                ->addColumn('subscription_status', [['get' => ['field' => 'status']]])
                ->addColumn('data_source', [['const' => 'subscription_api']])
            ),
        exceptionsToCatch: [\Exception::class],
        nbAttempts: 3
    ))
    
    // Branch 3: Purchase history (with retry)
    ->addMerge(new FailSafeConfig(
        chainConfig: (new ChainConfig())
            ->addLink(new SimpleHttpConfig(
                url: '@"https://api.example.com/purchases/"~data["ID"]',
                method: 'GET',
                responseIsJson: true
            ))
            ->addLink((new RuleTransformConfig(false))
                ->addColumn('customer_id', [['get' => ['field' => 'ID']]])
                ->addColumn('total_purchases', [['get' => ['field' => 'total']]])
                ->addColumn('last_purchase_date', [['get' => ['field' => 'last_date']]])
                ->addColumn('data_source', [['const' => 'purchase_api']])
            ),
        exceptionsToCatch: [\Exception::class],
        nbAttempts: 3
    ))
    
    // Branch 4: Calculated metrics
    ->addMerge((new ChainConfig())
        ->addLink(new CallBackTransformerConfig(function(DataItem $item) {
            $data = $item->getData();
            
            // Calculate customer lifetime value
            $lifetimeValue = calculateLifetimeValue($data);
            $segment = $lifetimeValue > 1000 ? 'premium' : 'regular';
            
            return new DataItem([
                'customer_id' => $data['ID'],
                'lifetime_value' => $lifetimeValue,
                'segment' => $segment,
                'calculated_at' => date('Y-m-d H:i:s'),
                'data_source' => 'calculated'
            ]);
        }))
    )
);

// Write all enriched data
$chainConfig->addLink(new CsvFileWriterConfig('customer-360-view.csv'));

// Execute
$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem(['file' => 'customers.csv'])]),
    []
);
```

## Common Use Cases

- **Customer 360 View**: Combine CRM, orders, support tickets, analytics
- **Product Enrichment**: Add images, reviews, inventory from different systems
- **Multi-API Aggregation**: Fetch related data from multiple APIs
