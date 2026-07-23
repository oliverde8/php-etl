<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation\Grouping;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\ChainBreakItem;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\OperationConfig\Grouping\BatchConfig;

class BatchOperation extends AbstractChainOperation implements DataChainOperationInterface, ConfigurableChainOperationInterface
{
    private array $buffer = [];

    public function __construct(private readonly BatchConfig $config)
    {}

    #[\Override]
    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $this->buffer[] = $item->getData();

        if (count($this->buffer) < $this->config->size) {
            return new ChainBreakItem();
        }

        return new DataItem($this->flushBuffer());
    }

    public function processStop(StopItem $stopItem, ExecutionContext $context): ItemInterface
    {
        if (empty($this->buffer)) {
            return $stopItem;
        }

        return new DataItem($this->flushBuffer());
    }

    private function flushBuffer(): array
    {
        $batch = $this->buffer;
        $this->buffer = [];

        return $batch;
    }
}
