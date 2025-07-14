---
layout: base
title: PHP-ETL - Operations
subTitle: Load - Json File(json-write)
---

The `json-write` operation writes `DataItem` objects (associative arrays) to a JSON file, encoding each as JSON and writing one item per line.

## Options

- **file:** The path to the JSON file to write to.

## Example

Here's an example of how to use the `json-write` operation to save transformed data to a new JSON file:

```yaml
chain:
  - operation: extract-json
    options:
      path: /path/to/input.json

  - operation: rule-transformer
    options:
      # Rules to transform the JSON data.
      columns:
        full_name:
          rules:
            - implode:
                values:
                  - { get: { field: "first_name" } }
                  - { get: { field: "last_name" } }
                with: " "

  - operation: json-write
    options:
      file: /path/to/output.json
```