#### Creating an ETL chain

To create an ETL chain in Symfony, you need to create a service that implements `ChainDefinitionInterface`.
The chain is built using typed PHP configuration objects.

**1. Create a chain definition service:**

```php
<?php

namespace App\Etl\ChainDefinition;

use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\PhpEtlBundle\Etl\ChainDefinitionInterface\ChainDefinitionInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

class CustomerImportDefinition implements ChainDefinitionInterface
{
    public function getKey(): string
    {
        return 'customer-import';
    }

    public function build(): ChainConfig
    {
        return (new ChainConfig())
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

The interface has the `#[AutoconfigureTag('etl.chain_definition')]` attribute, so services implementing it are automatically tagged when autoconfigure is enabled (default in Symfony).

```yaml
services:
    App\Etl\ChainDefinition\CustomerImportDefinition:
        tags: ['etl.chain_definition']
```

**3. Configure maxAsynchronousItems and other chain settings:**

```php
class HighVolumeImportDefinition implements ChainDefinitionInterface
{
    public function getKey(): string
    {
        return 'high-volume-import';
    }

    public function build(): ChainConfig
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

**4. You can also inject dependencies** into your chain definition:

```php
class ApiImportDefinition implements ChainDefinitionInterface
{
    public function __construct(
        private string $apiUrl,
    ) {}

    public function getKey(): string
    {
        return 'api-import';
    }

    public function build(): ChainConfig
    {
        return (new ChainConfig())
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

#### Creating custom operations

Custom operations are automatically registered when they implement `ConfigurableChainOperationInterface`. The bundle's compiler pass discovers them and sets up dependency injection automatically.

**1. Create a config class:**

```php
<?php

namespace App\Etl\Config;

use Oliverde8\Component\PhpEtl\OperationConfig\OperationConfigInterface;

class CustomTransformConfig implements OperationConfigInterface
{
    public function __construct(
        public readonly string $targetField,
        public readonly string $transformation,
    ) {}
}
```

**2. Create the operation:**

```php
<?php

namespace App\Etl\Operation;

use App\Etl\Config\CustomTransformConfig;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Psr\Log\LoggerInterface;

class CustomTransformOperation implements ConfigurableChainOperationInterface
{
    public function __construct(
        private CustomTransformConfig $config,
        private ChainBuilderV2 $chainBuilder,
        private string $flavor,
        private LoggerInterface $logger, // Auto-injected by Symfony
    ) {}

    public function process(mixed $item, ?array &$output = null, mixed $context = null): void
    {
        $this->logger->info('Processing item', ['field' => $this->config->targetField]);
        
        // Your transformation logic here
        $item[$this->config->targetField] = strtoupper($item[$this->config->targetField] ?? '');
        
        $output[] = $item;
    }
}
```

The operation is automatically registered and all dependencies (except `$config`, `$chainBuilder`, and `$flavor`) are auto-injected using Symfony's autowiring.

**3. Use it in your chain:**

```php
class MyChainDefinition implements ChainDefinitionInterface
{
    public function getKey(): string
    {
        return 'my-custom-chain';
    }

    public function build(): ChainConfig
    {
        return (new ChainConfig())
            ->addLink(new CsvExtractConfig())
            ->addLink(new CustomTransformConfig('email', 'uppercase'))
            ->addLink(new CsvFileWriterConfig('output/transformed.csv'));
    }
}
```

**How automatic dependency injection works:**

The `ChainBuilderV2Compiler` compiler pass:
- Discovers all services implementing `ConfigurableChainOperationInterface`
- Identifies the config class from the constructor (must implement `OperationConfigInterface`)
- Resolves all constructor dependencies at compile time using Symfony's dependency injection
- Creates a `GenericChainFactory` for each operation with resolved dependencies
- Automatically skips injection for `$config`, `$chainBuilder`, and `$flavor` (handled by the factory)

**Manual dependency injection:**

If you need more control over dependency injection, you can configure arguments manually:

```yaml
services:
    App\Etl\Operation\CustomTransformOperation:
        arguments:
            $logger: '@monolog.logger.etl'
```

Or use the `#[Autowire]` attribute in PHP 8.1+:

```php
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class CustomTransformOperation implements ConfigurableChainOperationInterface
{
    public function __construct(
        private CustomTransformConfig $config,
        private ChainBuilderV2 $chainBuilder,
        private string $flavor,
        #[Autowire(service: 'monolog.logger.etl')]
        private LoggerInterface $logger,
        #[Autowire('%app.etl.batch_size%')]
        private int $batchSize,
    ) {}
}
```

The compiler pass respects:
- Manually configured service arguments
- `#[Autowire]` attributes on constructor parameters
- Default parameter values
- Nullable parameters

#### Executing a chain

```sh
./bin/console etl:execute customer-import '[["test1"],["test2"]]' '{"opt1": "val1"}'
```

The first argument is the chain key (returned by `getKey()`).
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
