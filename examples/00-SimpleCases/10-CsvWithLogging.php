<?php

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\LogConfig;

require_once __DIR__ . '/../.init.php';
/** @var ChainBuilderV2 $chainBuilder */

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new LogConfig(
        message: 'Starting CSV extraction',
        level: 'info'
    ))
    ->addLink(new CsvExtractConfig())
    ->addLink(new LogConfig(
        message: '@"Processing customer: " ~ data["FirstName"]',
        level: 'debug'
    ));

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem('data/customers.csv')]),
    []
);

