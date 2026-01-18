<?php

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainRepeatConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SplitItemConfig;

require_once __DIR__ . '/../.init.php';
/** @var ChainBuilderV2 $chainBuilder */

// This example demonstrates using ChainRepeatOperation for API pagination
// It simulates fetching paginated data from an API

$chainConfig = new ChainConfig();

$page = 1;

// Create a repeat configuration that will continue fetching pages until there's no next page
$repeatConfig = new ChainRepeatConfig(
    chainConfig: (new ChainConfig())
        ->addLink(new CallBackTransformerConfig(function(DataItemInterface $dataItem) use (&$page) {
            $totalPages = 5; // Simulate 5 pages of data

            echo "Fetching page {$page}/{$totalPages}...\n";

            // Simulate API response with paginated data
            $items = [];
            for ($i = 1; $i <= 10; $i++) {
                $itemId = (($page - 1) * 10) + $i;
                $items[] = [
                    'id' => $itemId,
                    'name' => "Item {$itemId}",
                    'page' => $page,
                ];
            }

            $hasNextPage = $page < $totalPages;

            return new DataItem([
                'items' => $items,
                'page' => $page++,
                'hasNextPage' => $hasNextPage,
            ]);
        })),
    validationExpression: 'data["hasNextPage"] == true',
    allowAsynchronous: false
);

$chainConfig
    ->addLink($repeatConfig)
    ->addLink(new SplitItemConfig(keys: ['items']))
    ->addLink(new CsvFileWriterConfig('paginated-results.csv'));

$chainProcessor = $chainBuilder->createChain($chainConfig);
$chainProcessor->process(
    new ArrayIterator([
        new DataItem([[]]),
    ]),
    []
);


