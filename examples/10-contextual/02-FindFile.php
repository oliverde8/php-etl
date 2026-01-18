<?php

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\ExternalFileFinderConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\ExternalFileProcessorConfig;

require_once __DIR__ . '/.init.php';
/** @var ChainBuilderV2 $chainBuilder */
/** @var array $options */

// Prepare demo file
$dir = __DIR__ . "/02-find-file";
copy("$dir/file1-demo.csv", "$dir/file1.csv");

$options['dir'] = $dir;

$chainConfig = new ChainConfig();
$chainConfig->addLink(new ExternalFileFinderConfig(directory: $dir))
    ->addLink(new ExternalFileProcessorConfig())
    ->addLink(new CsvExtractConfig())
    ->addLink(new CsvFileWriterConfig('output.csv'))
    ->addLink(new ExternalFileProcessorConfig());

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(new ArrayIterator([new DataItem('/^file[0-9]\.csv$/')]), $options);

