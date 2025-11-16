<?php

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainMergeConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;

require_once __DIR__ . '/../.init.php';
/** @var ChainBuilderV2 $chainBuilder */

$chainConfig = new ChainConfig();
$chainConfig
    ->addLink(new CsvExtractConfig())
    ->addLink((new ChainMergeConfig())
        ->addMerge((new ChainConfig())
            ->addLink((new RuleTransformConfig(false))
                ->addColumn('customer_id', [['get' => ['field' => 'ID']]])
                ->addColumn('full_name', [
                    ['implode' => [
                        'values' => [
                            [[ 'get' => [ 'field' => 'FirstName' ]]],
                            [[ 'get' => [ 'field' => 'LastName' ]]],
                        ],
                        'with' => ' ',
                    ]]
                ])
            )
        )
        ->addMerge((new ChainConfig())
            ->addLink((new RuleTransformConfig(false))
                ->addColumn('customer_id', [['get' => ['field' => 'ID']]])
                ->addColumn('status', [['get' => ['field' => 'subscribed']]])
            )
        )

    )
    ->addLink(new CallBackTransformerConfig(function (DataItem $dataItem) {
        var_dump($dataItem->getData());
        return $dataItem;
    }));

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem('data/customers.csv')]),
    []
);
