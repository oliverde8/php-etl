<?php

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CommandCsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;

require_once __DIR__ . '/.init.php';
/** @var ChainBuilderV2 $chainBuilder */
/** @var callable $observer */

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new CommandCsvExtractConfig(['xan', 'filter', '-d', ';', 'keep == 1', '{file}']), 'extract')
    ->addLink(new CsvFileWriterConfig('data/output-xan.csv'), 'write');

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(new DataItem(['file' => 'data/bench.csv.gz']), [], $observer);
