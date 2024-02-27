<?php

namespace Oliverde8\Component\PhpEtl\Model\State;

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

    /** @var AsyncItemInterface[] */
    private array $asynInProgress = [];

    /**
     * @param string $operationName
     */
    public function __construct(string $operationName)
    {
        $this->operationName = $operationName;
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

    public function getAsyncWaiting(): int
    {
        return count($this->asynInProgress);
    }

    protected function processItem(ItemInterface $item)
    {
        if ($item instanceof StopItem) {
            $this->state = OperationStateEnum::Stopping;
        } elseif (!$item instanceof ChainBreakItem) {
            $this->state = OperationStateEnum::Running;
            $this->itemsProcessed++;
        }
    }

    protected function returnItem(ItemInterface $item)
    {
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
    }
}
