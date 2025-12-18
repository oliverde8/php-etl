---
layout: base
title: PHP-ETL - Operations
subTitle: Transform - Split Item(item-split)
---

The `item-split` operation divides a single data item into multiple items, useful for processing elements of a nested array individually.

## Options

- **keys:** An array of keys to extract from the input item. A new item will be created for each key.
- **single_element:** (Optional) If set to `true`, the operation will treat the first key in the `keys` array as an array and create a new item for each element of that array.
- **keep_keys:** (Optional) If set to `true`, the new items will be associative arrays containing `key` and `value` keys, where `key` is the original key from the input item.
- **key_name:** (Optional) If specified, the data for each new item will be placed under this key.
- **duplicate_keys:** (Optional) An array of keys to be copied from the original item to each new item.

## Examples

**Example 1: Splitting an array into multiple items**

This example shows how to split an array of addresses into separate items.

**Input:**

```json
{
  "customer_id": 123,
  "addresses": [
    { "street": "123 Main St", "city": "Anytown" },
    { "street": "456 Oak Ave", "city": "Someville" }
  ]
}
```

**Configuration:**

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SplitItemConfig;

$chainConfig = new ChainConfig();
$chainConfig->addLink(new SplitItemConfig(
    keys: ['addresses'],
    singleElement: true,
    duplicateKeys: ['customer_id' => 'customer_id']
));
```

**Output:**

Two items will be created:

```json
{
  "customer_id": 123,
  "street": "123 Main St",
  "city": "Anytown"
}
```

```json
{
  "customer_id": 123,
  "street": "456 Oak Ave",
  "city": "Someville"
}
```

**Example 2: Splitting an item by keys**

This example shows how to create separate items for the `billing_address` and `shipping_address`.

**Input:**

```json
{
  "customer_id": 123,
  "billing_address": { "street": "123 Main St", "city": "Anytown" },
  "shipping_address": { "street": "456 Oak Ave", "city": "Someville" }
}
```

**Configuration:**

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SplitItemConfig;

$chainConfig = new ChainConfig();
$chainConfig->addLink(new SplitItemConfig(
    keys: ['billing_address', 'shipping_address'],
    keepKeys: true,
    keyName: 'address'
));
```

**Output:**

Two items will be created:

```json
{
  "key": "billing_address",
  "value": {
    "address": { "street": "123 Main St", "city": "Anytown" }
  }
}
```

```json
{
  "key": "shipping_address",
  "value": {
    "address": { "street": "456 Oak Ave", "city": "Someville" }
  }
}
```
