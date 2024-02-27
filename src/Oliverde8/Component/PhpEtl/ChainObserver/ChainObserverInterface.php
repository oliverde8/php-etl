<?php

namespace Oliverde8\Component\PhpEtl\ChainObserver;

use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;

interface ChainObserverInterface
{
    public function init(array $chainLinks, array $chainNames): void;

    public function onBeforeProcess($operationId, ChainOperationInterface $operation, ItemInterface $item): void;

    public function onAfterProcess($chainId, ChainOperationInterface $operation, ItemInterface $item): void;
}