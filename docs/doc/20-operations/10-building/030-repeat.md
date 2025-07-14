---
layout: base
title: PHP-ETL - Operations
subTitle: Building Blocks - Chain Repeat(repeat)
---

The `repeat` operation executes a chain of operations repeatedly until a specified condition is met. 
This is useful for processing data in a loop, such as paginating through API results or processing a file until its end.

## Options

- **chain:** The chain of operations to be executed in each iteration.
- **while:** An expression that determines whether the loop should continue. The expression is evaluated before each iteration. The loop continues as long as the expression evaluates to `true`.
- **allow_async:** (Optional) A boolean that specifies whether the operations within the loop can be executed asynchronously. Defaults to `false`.

## Example

Here's an example of how to use the `repeat` operation to fetch and process data from a paginated API:

```yaml
chain:
  - operation: repeat
    options:
      while: "context.page <= context.totalPages"
      allow_async: true
      chain:
        - operation: http-request
          options:
            url: "https://api.example.com/data?page={{ context.page }}"
            method: GET

        - operation: rule-transformer
          options:
            # Rules to transform the API response.

        - operation: load-to-database
          options:
            # Options to load the data into a database.

        - operation: expression
          options:
            expression: "context.page = context.page + 1"
```