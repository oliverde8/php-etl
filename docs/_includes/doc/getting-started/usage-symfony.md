#### Creating an ETL chain

To create an ETL chain in Symfony, you need to create a service that implements `ChainDescriptionInterface`.
The chain is built using typed PHP configuration objects.

**1. Create a chain description service:**

```php
<?php

namespace App\Etl;

use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\ChainDescriptionInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

class CustomerImportChain implements ChainDescriptionInterface
{
    public function getName(): string
    {
        return 'customer-import';
    }

    public function getChain(): ChainConfig
    {
        $chainConfig = new ChainConfig();

        return $chainConfig
            ->addLink(new CsvExtractConfig())
            ->addLink((new RuleTransformConfig(false))
                ->addColumn('customer_id', [['get' => ['field' => 'ID']]])
                ->addColumn('full_name', [
                    ['implode' => [
                        'values' => [
                            [['get' => ['field' => 'FirstName']]],
                            [['get' => ['field' => 'LastName']]],
                        ],
                        'with' => ' ',
                    ]]
                ])
                ->addColumn('email', [['get' => ['field' => 'Email']]])
            )
            ->addLink(new CsvFileWriterConfig('output/customers.csv'));
    }
}
```

**2. Register the service** (if not using autoconfigure):

```yaml
services:
    App\Etl\CustomerImportChain:
        tags: ['php_etl.chain_description']
```

With Configure maxAsynchronousItems and other chain settings:**

```php
class HighVolumeImportChain implements ChainDescriptionInterface
{
    public function getName(): string
    {
        return 'high-volume-import';
    }

    public function getChain(): ChainConfig
    {
        $chainConfig = new ChainConfig();
        $chainConfig->setMaxAsynchronousItems(100); // Process up to 100 items in parallel

        return $chainConfig
            ->addLink(new CsvExtractConfig())
            ->addLink((new RuleTransformConfig(false))
                ->addColumn('id', [['get' => ['field' => 'ID']]])
            )
            ->addLink(new CsvFileWriterConfig('output/processed.csv'));
    }
}
```

**4. You can also inject dependencies** into your chain:

```php
class ApiImportChain implements ChainDescriptionInterface
{
    public function __construct(
        private string $apiUrl,
    ) {}

    public function getName(): string
    {
        return 'api-import';
    }

    public function getChain(): ChainConfig
    {
        $chainConfig = new ChainConfig();

        return $chainConfig
            ->addLink(new SimpleHttpConfig(
                url: $this->apiUrl,
                method: 'GET',
                responseIsJson: true
            ))
            ->addLink(new LogConfig(
                message: 'Imported record',
                level: 'info'
            ))
            ->addLink(new CsvFileWriterConfig('output/api-data.csv'));
    }
}
```

#### Executing a chain

```sh
./bin/console etl:execute customer-import '[["test1"],["test2"]]' '{"opt1": "val1"}'
```

The first argument is the chain name (returned by `getName()`).
The second argument is the input data, depending on your chain it can be empty or a JSON array.
The third argument contains parameters that will be available in the execution context.

#### Get a definition

```sh
./bin/console etl:get-definition customer-import
```

This displays the chain configuration and all its operations.

#### Get definition graph

```sh
./bin/console etl:definition:graph customer-import
```

This returns a Mermaid graph visualization of your ETL chain. Adding `-u` will return the URL to the Mermaid graph image.
