<?php

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SplitItemConfig;
use Symfony\Component\HttpClient\HttpClient;

require_once __DIR__ . '/../.init.php';
/** @var ChainBuilderV2 $chainBuilder */

$chainConfig = new ChainConfig();
$chainConfig->addLink(new SimpleHttpConfig(
        method: 'GET',
        url: 'https://63b687951907f863aaf90ab1.mockapi.io/test',
        responseIsJson: true
    ))
    ->addLink(new SplitItemConfig(
        keys: ['content'],
        singleElement: true
    ))
    ->addLink(new CsvFileWriterConfig('output.csv'));

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem([])]),
    []
);

