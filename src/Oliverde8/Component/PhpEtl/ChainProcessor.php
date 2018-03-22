<?php

namespace Oliverde8\Component\PhpEtl;

use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\ChainBreakItem;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\GroupedItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\StopItem;

/**
 * Class ChainProcessor
 *
 * @author    de Cramer Oliver<oliverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl
 */
class ChainProcessor
{
    /** @var ChainOperationInterface[] */
    protected $chainLinks = [];

    /** @var string[] */
    protected $chainLinkNames = [];

    /**
     * ChainProcessor constructor.
     *
     * @param ChainOperationInterface[] $chainLinks
     */
    public function __construct(array $chainLinks)
    {
        $this->chainLinkNames = array_keys($chainLinks);
        $this->chainLinks = array_values($chainLinks);
    }

    public function process(\Iterator $items, $context)
    {
        $this->processItems($items, 0, $context);
    }

    public function processItems(\Iterator $items, $startAt, &$context)
    {
        foreach ($items as $item) {
            $dataItem = new DataItem($item);
            $this->processItem($dataItem, $startAt, $context);
        }

        $last = $this->processItem(new StopItem(), $startAt, $context);
    }

    public function processItem(ItemInterface $item, $startAt, &$context)
    {
        for ($chainNumber = $startAt; $chainNumber < count($this->chainLinks); $chainNumber++) {
            $item = $this->chainLinks[$chainNumber]->process($item, $context);

            if ($item instanceof GroupedItemInterface) {
                return $this->processItems($item->getIterator(), $chainNumber + 1, $context);
            } else if ($item instanceof ChainBreakItem) {
                return PHP_INT_MAX;
            }
        }

        return $chainNumber;
    }
}
