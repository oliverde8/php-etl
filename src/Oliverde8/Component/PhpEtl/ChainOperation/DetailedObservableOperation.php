<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation;

use Oliverde8\Component\PhpEtl\Model\State\OperationState;

interface DetailedObservableOperation
{
    /**
     * @return OperationState[][]
     */
    public function getLastObservedState(): array;
}