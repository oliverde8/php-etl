<?php

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\RuleTransformConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;

require_once __DIR__ . '/../.init.php';
/** @var ChainBuilderV2 $chainBuilder */

$chainConfig = new ChainConfig();
$chainConfig->addLink(new SimpleHttpConfig(
        method: 'GET',
        url: '@"https://63b687951907f863aaf90ab1.mockapi.io/test/"~data["id"]',
        responseIsJson: true,
        optionKey: '-placeholder-',
    ))
    ->addLink((new RuleTransformConfig(false))
        ->addColumn('createdAt', [['get' => ['field' => ['content', 'createdAt']]]])
        ->addColumn('name', [['get' => ['field' => ['content', 'name']]]])
        ->addColumn('avatar', [['get' => ['field' => ['content', 'avatar']]]])
        ->addColumn('id', [['get' => ['field' => ['content', 'id']]]])
    )
    ->addLink(new CsvFileWriterConfig('output.csv'));

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([
        ["id" => 1],
        ["id" => 2],
        ["id" => 3],
        ["id" => 4],
        ["id" => 5],
        ["id" => 6],
        ["id" => 7],
        ["id" => 8],
        ["id" => 9],
        ["id" => 10],
        ["id" => 11],
        ["id" => 12],
        ["id" => 13],
        ["id" => 14],
        ["id" => 15],
        ["id" => 16],
        ["id" => 17],
        ["id" => 18],
        ["id" => 19],
    ]),
    []
);

