### Isolating Context

By default, the sub-chain shares the same execution context as the rest of the pipeline, so a parameter it
sets is visible outside the {{ include.unit }} too. Pass `isolateContext: true` to run the whole {{ include.unit }}
against its own clone instead:

```php
${{ include.var }} = new {{ include.config }}(
    // ...
    isolateContext: true
);
```

This only isolates the {{ include.unit }} from the *parent* context — state still carries over
{{ include.persistNote }}, it just won't be visible once the operation finishes. The file system and logger
stay shared — only context parameters are isolated.

See [Execution Context](/doc/01-understand-the-etl/execution-context.html#isolating-context-in-sub-chains) for more.
