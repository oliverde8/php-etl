<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation\Grouping;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\ChainBreakItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\GroupedItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;

/**
 * Class SimpleGrouping
 *
 * @author    de Cramer Oliver<oliverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\ChainOperation\Grouping
 */
class SimpleGroupingOperation extends AbstractChainOperation implements DataChainOperationInterface
{
    /** @var string[] Key to use for grouping, if array it will be used to read recursively inside the array.  */
    protected $groupKey = [];

    /** @var string[] Key to identify each individual data inside the group. */
    protected $groupIdentifierKey = [];

    /**
     * Grouped data kept in memory.
     *
     * @var array
     */
    protected $data = [];

    /**
     * SimpleGroupingOperation constructor.
     *
     * @param string[] $groupKey Key to use for grouping, if array it will be used to read recursively inside the array.
     * @param string[] $groupIdentifierKey key to identify each individual data inside the group.
     */
    public function __construct(array $groupKey, array $groupIdentifierKey = [])
    {
        $this->groupKey = $groupKey;
        $this->groupIdentifierKey = $groupIdentifierKey;
    }


    /**
     * @inheritdoc
     */
    #[\Override]
    public function processData(DataItemInterface $item, ExecutionContext $context): ChainBreakItem
    {
        $groupingValue = AssociativeArray::getFromKey($item->getData(), $this->groupKey);

        if (!empty($this->groupIdentifierKey)) {
            $groupIdValue = AssociativeArray::getFromKey($item->getData(), $this->groupIdentifierKey);
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