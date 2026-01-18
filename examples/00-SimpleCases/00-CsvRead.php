<?php

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;

require_once __DIR__ . '/../.init.php';
/** @var ChainBuilderV2 $chainBuilder */

$chainConfig = new ChainConfig();
$chainConfig->addLink(New CsvExtractConfig());
$chainConfig->addLink(new CallBackTransformerConfig(function (DataItem $dataItem) {
    var_dump($dataItem->getData());
    return $dataItem;
}));

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new DataItem([
        'file' => 'data/customers.csv',
    ]),
    []
);