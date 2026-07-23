<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainProcessorInterface;

/**
 * Implemented by composite operations that hold one or more nested chain processors as branches
 * (e.g. Split, Merge), so tooling like the Mermaid output can recurse into them.
 */
interface SubChainsAwareOperationInterface
{
    /**
     * @return ChainProcessorInterface[]
     */
    public function getChainProcessors(): array;
}
