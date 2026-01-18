<?php

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainSplitConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;

require_once __DIR__ . '/../.init.php';
/** @var ChainBuilderV2 $chainBuilder */

$chainConfig = new ChainConfig();
$chainConfig->addLink(new CsvExtractConfig())
    ->addLink(
        (new ChainSplitConfig())
            ->addSplit(
                (new ChainConfig())
                    ->addLink(new FilterDataConfig([["get" => ["field" => 'IsSubscribed']]]))
                    ->addLink(new CsvFileWriterConfig('customers-subscribed.csv'))
            )
            ->addSplit(
                (new ChainConfig())
                    ->addLink(new FilterDataConfig([["get" => ["field" => 'IsSubscribed']]], true))
                    ->addLink(new CsvFileWriterConfig('customers-not-subscribed.csv'))
            )
    )
    ->addLink(new CsvFileWriterConfig('customers-all.csv'));

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem(['file' => 'data/customers.csv',]), new DataItem(['file' => 'data/customers2.csv',])]),
    []
);
