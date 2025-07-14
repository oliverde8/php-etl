---
layout: base
title: PHP-ETL - Operations
subTitle: Transform - Callback
---

The `callback` operation executes a custom PHP function within your ETL chain, useful for complex transformations not covered by built-in operations.

**Note:** This operation is for programmatic use in PHP; it cannot be configured via YAML due to callback serialization limitations.

## Options

- **callback:** A PHP callable (e.g., a closure, a function name as a string, or an array with a class and method name).

## Example

Here's an example of how to use the `callback` operation to transform a data item with a custom function:

```php
<?php

use Oliverde8\Component\PhpEtl\ChainBuilder;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\CallbackTransformerOperation;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;

$chainBuilder = new ChainBuilder();

$chainBuilder->add(
    new CallbackTransformerOperation(
        function (ItemInterface $item, ExecutionContext $context) {
            $data = $item->getData();
            $data['full_name'] = $data['first_name'] . ' ' . $data['last_name'];
            return new DataItem($data);
        }
    )
);

// ...
```
