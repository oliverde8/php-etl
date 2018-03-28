<?php

namespace Oliverde8\Component\PhpEtl;

use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\Exception\ChainOperationException;
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
class ChainProcessor implements ChainProcessorInterface
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

    /**
     * Process items.
     *
     * @param \Iterator $items
     * @param $context
     * @throws ChainOperationException
     */
    public function process(\Iterator $items, $context)
    {
        if (!isset($context['etl']['identifier'])) {
            $context['etl']['identifier'] = '';
        }

        $this->processItems($items, 0, $context);
    }

    /**
     * Process list of items with chain starting at $startAt.
     *
     * @param \Iterator $items
     * @param int $startAt
     * @param array $context
     *
     * @return ItemInterface
     * @throws ChainOperationException
     */
    protected function processItems(\Iterator $items, $startAt, &$context)
    {
        $identifierPrefix = $context['etl']['identifier'];

        $count = 1;
        foreach ($items as $item) {
            $context['etl']['identifier'] = $identifierPrefix . $count++;

            $dataItem = new DataItem($item);
            $this->processItem($dataItem, $startAt, $context);
        }

        $stopItem = new StopItem();
        $context['etl']['identifier'] = $identifierPrefix . 'STOP';
        while ($this->processItem($stopItem, $startAt, $context) !== $stopItem);

        return $stopItem;
    }

    /**
     * Process an item, with chains starting at.
     *
     * @param ItemInterface $item
     * @param $startAt
     * @param $context
     *
     * @return mixed|ItemInterface|StopItem
     * @throws ChainOperationException
     */
    public function processItem(ItemInterface $item, $startAt, &$context)
    {
        for ($chainNumber = $startAt; $chainNumber < count($this->chainLinks); $chainNumber++) {
            $item = $this->processItemWithOperation($item, $chainNumber, $context);

            if ($item instanceof GroupedItemInterface) {
                $context['etl']['identifier'] .= "chain link:" . $this->chainLinkNames[$chainNumber] . "-";
                $this->processItems($item->getIterator(), $chainNumber + 1, $context);

                return new StopItem();
            } else if ($item instanceof ChainBreakItem) {
                return $item;
            }
        }

        return $item;
    }

    /**
     * Process an item and handle errors during the process.
     *
     * @param $item
     * @param $chainNumber
     * @param $context
     *
     *
     * @return ItemInterface
     * @throws ChainOperationException
     */
    protected function processItemWithOperation($item, $chainNumber, &$context)
    {
        try {
            return $this->chainLinks[$chainNumber]->process($item, $context);
        } catch (\Exception $exception) {
            throw new ChainOperationException(
                "An exception was thrown during the handling of the chain link : "
                    . "{$this->chainLinkNames[$chainNumber]} "
                    . "with the item {$context['etl']['identifier']}.",
                0,
                $exception,
                $this->chainLinkNames[$chainNumber]
            );
        }
    }
}
