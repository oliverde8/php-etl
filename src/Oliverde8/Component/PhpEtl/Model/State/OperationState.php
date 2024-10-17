<?php

namespace Oliverde8\Component\PhpEtl\Model\State;

use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\DetailedObservableOperation;
use Oliverde8\Component\PhpEtl\Item\AsyncItemInterface;
use Oliverde8\Component\PhpEtl\Item\ChainBreakItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\StopItem;

class OperationState
{
    private readonly string $operationName;

    private OperationStateEnum $state = OperationStateEnum::Waiting;

    private int $itemsProcessed = 0;

    private int $itemsReturned = 0;

    private int $timeSpent = 0;

    private int $lastStartTime = 0;

    /** @var AsyncItemInterface[] */
    private array $asynInProgress = [];

    private array $subStates = [];

    public function __construct(string $operationName, ChainOperationInterface $operation)
    {
        $this->operationName = $operationName;

        if ($operation instanceof DetailedObservableOperation) {
            $this->subStates = $operation->getLastObservedState();
        }
    }

    public function getOperationName(): string
    {
        return $this->operationName;
    }

    public function getState(): OperationStateEnum
    {
        return $this->state;
    }

    public function getItemsProcessed(): int
    {
        return $this->itemsProcessed;
    }

    public function getItemsReturned(): int
    {
        return $this->itemsReturned;
    }

    public function getTimeSpent(): int
    {
        return $this->timeSpent;
    }

    public function getAsyncWaiting(): int
    {
        return count($this->asynInProgress);
    }

    public function getSubStates(): array
    {
        return $this->subStates;
    }

    protected function processItem(ChainOperationInterface $operation, ItemInterface $item): void
    {
        $this->lastStartTime = floor(microtime(true) * 1000);

        if ($item instanceof StopItem) {
            $this->state = OperationStateEnum::Stopping;
        } elseif (!$item instanceof ChainBreakItem) {
            $this->state = OperationStateEnum::Running;
            $this->itemsProcessed++;
        }

        if ($operation instanceof DetailedObservableOperation) {
            $this->subStates = $operation->getLastObservedState();
        }
    }

    protected function returnItem(ChainOperationInterface $operation, ItemInterface $item): void
    {
        $this->timeSpent += floor(microtime(true) * 1000) - $this->lastStartTime;

        foreach ($this->asynInProgress as $key => $asynInProgress) {
            if (!$asynInProgress->isRunning()) {
                unset($this->asynInProgress[$key]);
            }
        }

        if ($item instanceof StopItem) {
            $this->state = OperationStateEnum::Stopped;
        } elseif ($item instanceof AsyncItemInterface) {
            $this->state = OperationStateEnum::Async;
            $this->asynInProgress[] = $item;
        } elseif (!$item instanceof ChainBreakItem) {
            $this->itemsReturned++;

            if (count($this->asynInProgress) == 0) {
                $this->state = OperationStateEnum::Waiting;
            }
        }

        if ($operation instanceof DetailedObservableOperation) {
            $this->subStates = $operation->getLastObservedState();
        }
    }
}
