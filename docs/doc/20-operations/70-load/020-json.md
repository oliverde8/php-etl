---
layout: base
title: PHP-ETL - Operations
subTitle: Load - Json File(json-write)
---

The `json-write` operation writes `DataItem` objects (associative arrays) to a JSON file, encoding each as JSON and writing one item per line.

## Options

- **file:** The path to the JSON file to write to.

## Example

Here's an example of how to use JSON file writing to save transformed data to a new JSON file:

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\JsonExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new JsonExtractConfig())
    ->addLink((new RuleTransformConfig(add: false))
        ->addColumn('full_name', [
            ['implode' => [
                'values' => [
                    [['get' => ['field' => 'first_name']]],
                    [['get' => ['field' => 'last_name']]],
                ],
                'with' => ' ',
            ]],
        ])
    );
    // Note: JsonFileWriterConfig is not yet available in v2.
    // Use the old YAML-based approach or contribute the implementation.
```