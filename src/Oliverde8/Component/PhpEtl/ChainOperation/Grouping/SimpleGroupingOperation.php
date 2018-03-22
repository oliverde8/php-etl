<?php

namespace Oliverde8\Component\PhpEtl\ChainOperation\Grouping;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\ChainBreakItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\GroupedItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\StopItem;

/**
 * Class SimpleGrouping
 *
 * @author    de Cramer Oliver<oliverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\ChainOperation\Grouping
 */
class SimpleGroupingOperation extends AbstractChainOperation implements DataChainOperationInterface
{
    public $groupKey = [];

    public $data = [];

    /**
     * SimpleGrouping constructor.
     *
     * @param array $groupKey
     */
    public function __construct(array $groupKey)
    {
        $this->groupKey = $groupKey;
    }


    public function processData(DataItemInterface $item, array &$context): ItemInterface
    {
        $groupingValue = AssociativeArray::getFromKey($item->getData(), $this->groupKey);
        $this->data[$groupingValue][] = $item->getData();

        return new ChainBreakItem();
    }

    public function processStop(StopItem $stopItem, array &$context): ItemInterface
    {
        return new GroupedItem(new \ArrayIterator($this->data));
    }
}