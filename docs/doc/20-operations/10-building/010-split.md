---
layout: base
title: PHP-ETL - Operations
subTitle: Building Blocks - Chain Split(split)
---

The `split` operation executes multiple, independent chains of operations with the same input data. 
Each chain, or "branch," processes data in parallel without affecting other branches or the main chain. 
This is useful for performing distinct tasks simultaneously, such as logging, sending to an API, and saving to a database.

## Options

- **branches:** An array of chains of operations.

## Example

Here's an example of how to use the `split` operation to process a CSV file in two different ways simultaneously:

```yaml
chain:
  - operation: extract-csv
    options:
      path: /path/to/input.csv

  - operation: split
    options:
      branches:
        - - operation: rule-transformer
            options:
              # ... rules for transformation
          - operation: load-csv
            options:
              path: /path/to/output.csv

        - - operation: log
            options:
              message: "Processing item {{ item.id }}"
```
