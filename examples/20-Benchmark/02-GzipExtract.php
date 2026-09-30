<?php

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Expression\Expression;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;

require_once __DIR__ . '/.init.php';
/** @var ChainBuilderV2 $chainBuilder */
/** @var callable $observer */

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new CsvExtractConfig(compression: 'gzip'), 'extract')
    ->addLink(new FilterDataConfig(new Expression('data["keep"] == "1"')), 'filter')
    ->addLink(new CsvFileWriterConfig('data/output-gzip.csv'), 'write');

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(new DataItem(['file' => 'data/bench.csv.gz']), [], $observer);
