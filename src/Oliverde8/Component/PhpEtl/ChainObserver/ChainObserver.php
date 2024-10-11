<?php

namespace Oliverde8\Component\PhpEtl\ChainObserver;

use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\State\OperationState;

class ChainObserver extends OperationState implements ChainObserverInterface
{
    /** @var OperationState[] */
    protected array $operationStates = [];

    protected $callable;

    /**
     * @param callable $closure
     */
    public function __construct(callable $closure)
    {
        $this->callable = $closure;
    }


    public function init(array $chainLinks, array $chainNames): void
    {
        foreach ($chainLinks as $id => $operation) {
            $this->operationStates[$id] = new OperationState($chainNames[$id], $operation);
        }

        $this->callback();
    }

    public function onBeforeProcess($operationId, ChainOperationInterface $operation, ItemInterface $item): void
    {
        $this->processItem($operation, $item);
        $this->operationStates[$operationId]->processItem($operation, $item);

        $this->callback();
    }

    public function onAfterProcess($operationId, ChainOperationInterface $operation, ItemInterface $item): void
    {
        $this->returnItem($operation, $item);
        $this->operationStates[$operationId]->returnItem($operation, $item);

        $this->callback();
    }

    private function callback()
    {
        $callback = $this->callable;
        $callback($this->operationStates, $this->getItemsProcessed(), $this->getItemsReturned());
    }

    public function getOperationStates(): array
    {
        return $this->operationStates;
    }
}