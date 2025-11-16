<?php

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\JsonExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;

require_once __DIR__ . '/../.init.php';
/** @var ChainBuilderV2 $chainBuilder */

$chainConfig = new ChainConfig();
$chainConfig->addLink(new JsonExtractConfig())
    ->addLink((new RuleTransformConfig(false))
        ->addColumn('productId', [['get' => ['field' => 'productId']]])
        ->addColumn('sku', [['get' => ['field' => 'sku']]])
        ->addColumn('name-{@context/locales}', [['get' => ['field' => ['name', '@context/locales']]]])
    )
    ->addLink(new CsvFileWriterConfig('products.csv'));

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([new DataItem('data/products.json')]),
    ['locales' => ['fr_FR', 'en_US']]
);
