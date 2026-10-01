---
layout: base
title: PHP-ETL - Operations
subTitle: Transform - Throttle(throttle)
---

The `throttle` operation enforces a minimum delay between items at a point in the chain. Use it before anything
rate-sensitive: a third-party API with a request limit, a database you don't want to hammer, etc.

Items are evenly spaced: if the previous item passed less than `intervalMs` milliseconds ago, the operation sleeps
for the remainder. The first item is never delayed. Items are not modified.

## Options

- **intervalMs:** Minimum time between two items, in milliseconds. Must be greater than 0 (`200` = at most 5 items per second).

## Example

Limit calls to an API to 5 per second:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\ThrottleConfig;

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new ThrottleConfig(intervalMs: 200))
    ->addLink(new SimpleHttpConfig(method: 'GET', url: '@"https://api.example.com/products/" ~ data["id"]'));
```
