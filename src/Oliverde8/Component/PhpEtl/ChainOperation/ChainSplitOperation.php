<?php

namespace Oliverde8\Component\PhpEtl\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\StopItem;

/**
 * Class ChainSplitOperation
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\ChainOperation
 */
class ChainSplitOperation extends AbstractChainOperation implements DataChainOperationInterface
{
    /** @var ChainProcessor[] */
    protected $chainProcessors;

    /**
     * ChainSplitOperation constructor.
     *
     * @param ChainProcessor[] $chainProcessors
     */
    public function __construct(array $chainProcessors)
    {
        $this->chainProcessors = $chainProcessors;
    }

    /**
     * Process DataItems.
     *
     * @param DataItemInterface $item
     * @param array $context
     *
     * @return ItemInterface
     * @throws \Oliverde8\Component\PhpEtl\Exception\ChainOperationException
     */
    public function processData(DataItemInterface $item, array &$context)
    {
        foreach ($this->chainProcessors as $chainProcessor) {
            $chainProcessor->processItem($item, 0,  $context);
        }

        // Nothing to process.
        return $item;
    }

    /**
     * @param StopItem $item
     * @param array $context
     *
     * @return StopItem
     * @throws \Oliverde8\Component\PhpEtl\Exception\ChainOperationException
     */
    public function processStop(StopItem $item, array &$context)
    {
        foreach ($this->chainProcessors as $chainProcessor) {
            $result = $chainProcessor->processItem($item, 0,  $context);

            if ($result !== $item) {
                // Return a new stop item in order to continue flushing out data with stop items.
                $item = new StopItem();
            }
        }

        return $item;
    }
}
