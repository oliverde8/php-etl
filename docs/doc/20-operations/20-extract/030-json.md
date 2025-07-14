---
layout: base
title: PHP-ETL - Operations
subTitle: Extract - JSON File(json-read)
---

The `json-read` operation reads a JSON file, outputting a `GroupedItem` with an iterator for the JSON data. It typically follows an operation providing a file path, like `file-finder`.

The operation receives a `DataItem` that contains the path to the csv file to read. It will return a list DataItem's.
Should e used after a `external file processor` operation.

## Options

- **file_key:** (Optional) If the input data is an array, the key (e.g., `key/subkey`) containing the JSON file path.

## Example

```yaml
chain:
  - operation: file-finder
    options:
      path: /path/to/input.json

  - operation: json-read

  - operation: rule-transformer
    options:
      # Rules to transform the JSON data.

  - operation: load-json
    options:
      path: /path/to/output.json
```