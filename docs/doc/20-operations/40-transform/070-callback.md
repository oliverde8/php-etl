---
layout: base
title: PHP-ETL - Operations
subTitle: Transform - Callback
---

The callback operation executes a custom PHP function within your ETL chain, useful for complex transformations not covered by built-in operations.

## Options

- **callback:** A PHP callable (e.g., a closure, a function name as a string, or an array with a class and method name).

## Example

Here's an example of how to use the callback operation to transform a data item with a custom function:

```php
<?php

use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;

$chainConfig = new ChainConfig();
$chainConfig->addLink(new CallBackTransformerConfig(
    function (DataItem $dataItem) {
        $data = $dataItem->getData();
        $data['full_name'] = $data['first_name'] . ' ' . $data['last_name'];
        return new DataItem($data);
    }
));

// ...
```
