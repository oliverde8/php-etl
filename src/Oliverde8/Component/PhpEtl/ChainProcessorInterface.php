<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl;

use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;

interface ChainProcessorInterface
{
    public function process(\Iterator|ItemInterface $item, array $parameters, ?callable $observerCallback = null, $withStop = true): void;

    public function processGenerator(
        \Iterator|ItemInterface $item,
        ExecutionContext $context,
        ?callable $observerCallback = null,
        bool $withStop = true,
        bool $allowAsynchronous = true
    ): \Generator;
}
