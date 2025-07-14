---
layout: base
title: PHP-ETL - Operations
subTitle: Building Blocks - Fail Safe(safe)
---

The `safe` operation (`FailSafeOperation`) handles exceptions within an ETL chain. It wraps a chain in a "safe" block, catching specified exceptions and retrying the chain a set number of times. If an exception is not caught or retries are exhausted, it's re-thrown. This enhances ETL process robustness against transient errors like network issues.

## Options

- **chain:** The chain of operations to be executed within the safe block.
- **exceptions_to_catch:** A list of exception classes to catch. If an exception of a different type is thrown, it will not be caught.
- **max_retries:** The maximum number of times to retry the chain if a caught exception is thrown.

## Example

Here's an example of how to use the `safe` operation to handle `ApiConnectionException` when making an HTTP request:

```yaml
chain:
  - operation: safe
    options:
      exceptions_to_catch:
        - App\Exception\ApiConnectionException
      max_retries: 3
      chain:
        - operation: http-request
          options:
            url: "https://api.example.com/data"
            method: GET

        - operation: rule-transformer
          options:
            # Rules to transform the API response.

  - operation: load-to-database
    options:
      # Options to load the data into a database.
```