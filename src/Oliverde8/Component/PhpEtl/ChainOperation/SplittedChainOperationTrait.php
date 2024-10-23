<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\Model\State\OperationState;

trait SplittedChainOperationTrait
{

    /** @var OperationState[][] */
    protected array $lastObservedState = [];

    protected function onSplittedChainOperationConstruct(array $chainProcessors): void
    {
        $lastObservedState = &$this->lastObservedState;
        foreach ($chainProcessors as $subChainPart => $chainProcessor) {
            if ($chainProcessor instanceof ChainProcessor) {
                $observer = $chainProcessor->initObserver(function (array $operationStates) use (&$lastObservedState, $subChainPart) {
                    $lastObservedState[$subChainPart] = $operationStates;
                });

                $lastObservedState[$subChainPart] = $observer->getOperationStates();
            }
        }
    }

    public function getLastObservedState(): array
    {
        return $this->lastObservedState;
    }
}