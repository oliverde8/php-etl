<?php

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\OperationConfig\FailSafeConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;

require_once __DIR__ . '/../.init.php';
/** @var ChainBuilderV2 $chainBuilder */

// Example demonstrating FailSafeOperation: will retry throwing callback up to 3 times before failing.

$failingChain = (new ChainConfig())
    ->addLink(new CallBackTransformerConfig(function(DataItem $dataItem) {
        static $attempt = 0; $attempt++;
        $data = $dataItem->getData();
        echo "Attempt {$attempt} for id {$data['id']}\n";
        if ($attempt < 3) {
            echo "  -> Simulating transient error\n";
            throw new RuntimeException('Transient error, please retry');
        }

        echo "  -> Success!\n";
        $data['status'] = 'success';
        $data['attempt'] = $attempt;
        return new DataItem($data);
    }));

$failSafeConfig = new FailSafeConfig(
    chainConfig: $failingChain,
    exceptionsToCatch: [RuntimeException::class],
    nbAttempts: 5,
);

$rootChain = new ChainConfig();
$rootChain->addLink($failSafeConfig);

$processor = $chainBuilder->createChain($rootChain);
$processor->process(
    new ArrayIterator([
        new DataItem(['id' => 1]),
    ]),
    []
);

