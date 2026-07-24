### Isolating Context

By default, all branches share the same execution context as the main chain — a parameter set with
`$context->setParameter()` inside one branch is visible in every other branch and in the main chain once the
{{ include.operation }} is done. Pass `isolateContext: true` to give each branch its own independent copy instead:

```php
${{ include.var }} = new {{ include.config }}(
    // ...
    isolateContext: true
);
```

Branches can then no longer see each other's context changes, and nothing leaks back to the main chain. The
file system and logger stay shared — only context parameters are isolated.

See [Execution Context](/doc/01-understand-the-etl/execution-context.html#isolating-context-in-sub-chains) for more.
