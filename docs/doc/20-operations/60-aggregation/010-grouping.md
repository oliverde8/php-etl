---
layout: base
title: PHP-ETL - Operations
subTitle: Aggregation/Grouping - Simple grouping (simple-grouping)
---

The `simple-grouping` operation groups items based on a common key, useful for data aggregation (e.g., grouping customers by city). It collects items in memory, then outputs a single `GroupedItem` with an iterator for the grouped data.

## Options

- **grouping_key:** An array of keys to use for grouping. The values of these keys will be combined to create a unique identifier for each group.
- **group_identifier:** (Optional) An array of keys to use for identifying individual items within a group. If specified, only the last item with a given identifier will be kept in the group.

## Example

Here's an example of how to use the `simple-grouping` operation to group a list of customers by their city.

**Input Data (a sequence of items):**

```json
[
  { "name": "John Doe", "city": "New York" },
  { "name": "Jane Doe", "city": "New York" },
  { "name": "Peter Jones", "city": "London" }
]
```

**YAML Configuration:**

```yaml
chain:
  - operation: simple-grouping
    options:
      grouping_key: ["city"]

  - operation: rule-transformer
    options:
      # Rules to process the grouped data.
      # The input to this operation will be an iterator of groups.
      # Each group will be an array of customers.
```

**Output:**

The `rule-transformer` will receive an iterator with two groups:

- **Group 1 (New York):**
  ```json
  [
    { "name": "John Doe", "city": "New York" },
    { "name": "Jane Doe", "city": "New York" }
  ]
  ```
- **Group 2 (London):**
  ```json
  [
    { "name": "Peter Jones", "city": "London" }
  ]
  ```