<?php

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;

require_once __DIR__ . '/../.init.php';
/** @var ChainBuilderV2 $chainBuilder */

$chainConfig = new ChainConfig();
$chainConfig->addLink(New CsvExtractConfig())
    ->addLink(new RuleTransformConfig(
        rules: [
            'Name' => [
                'rules' => [
                    ['implode' => [
                        'values' => [
                            [[ 'get' => [ 'field' => 'FirstName' ]]],
                            [[ 'get' => [ 'field' => 'LastName' ]]],
                        ],
                        'with' => ' ',
                    ]],
                ],
            ],
            'SubscriptionStatus' => [
                'rules' => [
                    ['get' => [ 'field' => 'IsSubscribed' ]]
                ],
            ],
        ],
        add: false,
        flavor: 'default'
    ))
    ->addLink(new CsvFileWriterConfig('customers-transformed.csv'))
    ->addLink(new CallBackTransformerConfig(function (DataItem $dataItem) {
        var_dump($dataItem->getData());
        return $dataItem;
    }))
;

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new DataItem([
        'file' => 'data/customers.csv',
    ]),
    []
);
