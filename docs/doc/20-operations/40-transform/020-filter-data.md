---
layout: base
title: PHP-ETL - Operations
subTitle: Transform - Filter Data(filter)
---

The `filter` operation selectively skips items in the chain based on a rule. It uses the [rule engine](/doc/20-operations/40-transform/030-rule-transformer) to evaluate a condition; if the condition is not met, the item is not passed to subsequent operations.

## Options

- **rule:** The rule to be evaluated. If the rule evaluates to a "truthy" value (not `null` and not `false`), the item is kept. Otherwise, it is filtered out.
- **negate:** (Optional) A boolean that, if set to `true`, inverts the result of the rule. In this case, items that evaluate to a "truthy" value are filtered out, and items that evaluate to a "falsy" value are kept.

## Example

Here's an example of how to use the `filter` operation to keep only the items where the `status` field is equal to `"published"`:

```yaml
chain:
  - operation: filter
    options:
      rule:
        #...

  - operation: load-to-database
    options:
      # Options to load the filtered data into a database.
```

Here's an example of how to use the `filter` operation with `negate` to filter out items where the `age` is less than `18`:

```yaml
chain:
  - operation: filter
    options:
      rule:
        #...
      negate: true

  - operation: load-to-database
    options:
      # Options to load the filtered data into a database.
```
