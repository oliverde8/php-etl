<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation\Grouping;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\ChainBreakItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\GroupedItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\OperationConfig\Grouping\SimpleGroupingConfig;

class SimpleGroupingOperation extends AbstractChainOperation implements DataChainOperationInterface, ConfigurableChainOperationInterface
{
    private array $data = [];

    public function __construct(private readonly SimpleGroupingConfig $config)
    {}

    /**
     * @inheritdoc
     */
    public function processData(DataItemInterface $item, ExecutionContext $context): ChainBreakItem
    {
        $groupingValue = AssociativeArray::getFromKey($item->getData(), $this->config->groupKey);

        if (!empty($this->config->groupIdentifierKey)) {
            $groupIdValue = AssociativeArray::getFromKey($item->getData(), $this->config->groupIdentifierKey);
            $this->data[$groupingValue][$groupIdValue] = $item->getData();
        } else {
            $this->data[$groupingValue][] = $item->getData();
        }

        return new ChainBreakItem();
    }

    /**
     * @inheritdoc
     */
    public function processStop(StopItem $stopItem, ExecutionContext $context): ItemInterface
    {
        if (empty($this->data)) {
            return $stopItem;
        }

        $data = $this->data;
        $this->data = [];

        return new GroupedItem(new \ArrayIterator($data));
    }
}