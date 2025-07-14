---
layout: base
title: PHP-ETL - Operations
subTitle: Building Blocks - Chain Merge(merge)
---

The `merge` operation executes multiple chains of operations (branches) with the same input data, then combines their results back into the main chain.
This is useful for performing different transformations on the same data and combining the outcomes.

**Warning:** If branches don't filter or modify items, subsequent steps will receive duplicate data,
as `merge` doesn't handle duplicates automatically. 
This behavior can be leveraged to create multiple versions of an item.

## Options

- **branches:** An array of chains of operations.

## Example

Here's an example of how to use the `merge` operation to create two different versions of a product from a single input item:

```yaml
chain:
  - operation: extract-csv
    options:
      path: /path/to/products.csv

  - operation: merge
    options:
      branches:
        - - operation: rule-transformer
            options:
              # Rules to create a simple product.
              rules:
                sku: "{{ item.sku }}-simple"
                type: simple

        - - operation: rule-transformer
            options:
              # Rules to create a configurable product.
              rules:
                sku: "{{ item.sku }}-configurable"
                type: configurable

  - operation: load-csv
    options:
      path: /path/to/merged-products.csv
```
