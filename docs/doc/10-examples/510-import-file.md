---
layout: base
title: PHP-ETL - Cook Books
subTitle: With Context - Import External File
width: large
---

### With Context - Import External File

The ETL can be used to fetch files from another filesystem and process them using the ETL. Files will be moved into
`processing` and `processed` directories as the ETL runs.

> 🎵 **Symfony Users**: If you're using the Flysystem Bundle, factories are automatically created for each storage.
> See the complete Symfony example below.



{% capture description %}
We will use the `ExternalFileFinderConfig` to find the files. And use the `ExternalFileProcessorConfig` to copy
the files to the context of the ETL execution.

The file finder will use a directory that we will add to the context at the beginning of the execution.

We will also need to provide the finder with a regex as chain input so that it can find all the files.
{% endcapture %}
{% capture code %}
```php
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\ExternalFileFinderConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\ExternalFileProcessorConfig;

$chainConfig
    ->addLink(new ExternalFileFinderConfig(
        directory: "@context['dir']"
    ))
    ->addLink(new ExternalFileProcessorConfig());
```
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

{% capture column1 %}
#### 🐘 Standalone

```php
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Model\PockExecution;

$options = [
    'etl' => [
        'execution' => new PockExecution(new DateTime())
    ],
    'dir' => $dir,
];

$chainProcessor->process(
    new ArrayIterator([new DataItem(['pattern' => '/^file[0-9]\.csv$/'])]),
    $options
);
```
{% endcapture %}
{% capture column2 %}
#### 🎵 Symfony

**With Flysystem (Automatic):**

First, configure your Flysystem storage:

```yaml
# config/packages/flysystem.yaml
flysystem:
    storages:
        default.storage:
            adapter: 'local'
            options:
                directory: '%kernel.project_dir%/var/storage/default'
```

Then create your chain definition:

```php
namespace App\Etl\ChainDefinition;

use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\ExternalFileFinderConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\ExternalFileProcessorConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\PhpEtlBundle\Etl\ChainDefinitionInterface\ChainDefinitionInterface;

class ImportFilesDefinition implements ChainDefinitionInterface
{
    public function getKey(): string
    {
        return 'etl:import-files';
    }

    public function build(): ChainConfig
    {
        return (new ChainConfig())
            ->addLink(new ExternalFileFinderConfig(
                directory: '/incoming',
                flavor: 'flysystem.default.storage'  // Auto-created!
            ))
            ->addLink(new ExternalFileProcessorConfig())
            ->addLink(new CsvExtractConfig())
            ->addLink(new CsvFileWriterConfig('output.csv'))
            ->addLink(new ExternalFileProcessorConfig());
    }
}
```

Execute:

```sh
./bin/console etl:execute etl:import-files '["/^file[0-9]\.csv$/"]' -p
```

**Manual (with context):**

```sh
./bin/console etl:execute myetl "['/^file[0-9]\.csv$/']" "{'dir': '/var/import'}"
```
{% endcapture %}
{% include block/2column.html column1=column1 column2=column2 %}

#### Complete Code

```php
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\ExternalFileFinderConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\ExternalFileProcessorConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Model\PockExecution;

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new ExternalFileFinderConfig(
        directory: "@context['dir']"
    ))
    ->addLink(new ExternalFileProcessorConfig())
    ->addLink(new CsvExtractConfig())
    ->addLink(new CsvFileWriterConfig('output.csv'))
    ->addLink(new ExternalFileProcessorConfig());

// Create and execute the chain with context
$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem(['pattern' => '/^file[0-9]\.csv$/'])]),
    [
        'etl' => [
            'execution' => new PockExecution(new DateTime())
        ],
        'dir' => '/var/import'
    ]
);
```
