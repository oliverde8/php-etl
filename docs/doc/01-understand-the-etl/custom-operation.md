---
layout: base
title: PHP-ETL - Understand the ETL
subTitle: Creating custom operations
width: large
---

Creating custom operations involves three main components:

1. A configuration class that holds the operation's parameters
2. An operation class that implements the actual logic
3. Registering the operation with the ChainBuilder

## Step 1: Create a Configuration Class

First, create a configuration class that extends `AbstractOperationConfig`. This class holds all the parameters needed for your operation.

```php
<?php

namespace App\Etl\OperationConfig;

use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;

class MyCustomConfig extends AbstractOperationConfig
{
    public function __construct(
        public readonly string $myParameter,
        public readonly bool $myFlag = false,
        string $flavor = 'default',
    ) {
        parent::__construct($flavor);
    }

    protected function validate(bool $constructOnly): void
    {
        // Add validation logic here if needed
    }
}
```

## Step 2: Create the Operation Class

Next, create the operation class that extends `AbstractChainOperation` and implements the appropriate interfaces.

For operations that process data items, implement `DataChainOperationInterface`:

```php
<?php

namespace App\Etl\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use App\Etl\OperationConfig\MyCustomConfig;

class MyCustomOperation extends AbstractChainOperation implements 
    DataChainOperationInterface, 
    ConfigurableChainOperationInterface
{
    public function __construct(private readonly MyCustomConfig $config)
    {
    }

    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $data = $item->getData();

        // Your custom logic here
        if ($this->config->myFlag) {
            $data[$this->config->myParameter] = 'modified';
        }

        return new DataItem($data);
    }
}
```

### Processing Specific Item Types

If your operation only processes data items, implement `DataChainOperationInterface` and use the `processData` method as shown above.

For operations that need to process all item types, you can use the generic `processItem` method:

```php
class MyGenericOperation extends AbstractChainOperation implements ConfigurableChainOperationInterface
{
    public function __construct(private readonly MyCustomConfig $config)
    {
    }

    protected function processItem(ItemInterface $item, ExecutionContext $context): ItemInterface
    {
        // Process any item type
        return $item;
    }
}
```

### Processing Multiple Item Types

If you need to handle different item types differently, you can create multiple methods with different type hints:

```php
use Oliverde8\Component\PhpEtl\Item\StopItem;

class MyMultiTypeOperation extends AbstractChainOperation implements ConfigurableChainOperationInterface
{
    public function __construct(private readonly MyCustomConfig $config)
    {
    }

    protected function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        // Handle data items
        return $item;
    }

    protected function processStopItem(StopItem $item, ExecutionContext $context): ItemInterface
    {
        // Handle stop items
        return $item;
    }
}
```

## Step 3: Register the Operation

Register your custom operation with the `ChainBuilderV2` using `GenericChainFactory`:

```php
<?php

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\GenericChainFactory;
use App\Etl\ChainOperation\MyCustomOperation;
use App\Etl\OperationConfig\MyCustomConfig;

$chainBuilder = new ChainBuilderV2(
    $contextFactory,
    [
        // ... other factories
        new GenericChainFactory(
            MyCustomOperation::class,
            MyCustomConfig::class
        ),
    ]
);
```

### With Injections

If your operation requires additional dependencies (like services or configuration), pass them via the `injections` parameter:

```php
use Psr\Log\LoggerInterface;

// In your operation constructor:
class MyCustomOperation extends AbstractChainOperation implements 
    DataChainOperationInterface, 
    ConfigurableChainOperationInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MyCustomConfig $config
    ) {
    }
    // ... rest of the class
}

// Register with injections:
$chainBuilder = new ChainBuilderV2(
    $contextFactory,
    [
        new GenericChainFactory(
            MyCustomOperation::class,
            MyCustomConfig::class,
            injections: ['logger' => $myLogger]
        ),
    ]
);
```

## Step 4: Use Your Custom Operation

Now you can use your custom operation in a chain configuration:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use App\Etl\OperationConfig\MyCustomConfig;

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new MyCustomConfig(
        myParameter: 'example',
        myFlag: true
    ))
    ->addLink(/* other operations */);

$chainProcessor = $chainBuilder->createChain($chainConfig);
```
