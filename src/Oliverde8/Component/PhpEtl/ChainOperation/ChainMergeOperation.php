<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;

/**
 * Class ChainSplitOperation
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\ChainOperation
 */
class ChainMergeOperation extends AbstractChainOperation implements DataChainOperationInterface, DetailedObservableOperation
{
    use SplittedChainOperationTrait;

    /** @var ChainProcessor[] */
    protected array $chainProcessors;

    /**
     * ChainSplitOperation constructor.
     *
     * @param ChainProcessor[] $chainProcessors
     */
    public function __construct(array $chainProcessors)
    {
        $this->chainProcessors = $chainProcessors;
        $this->onSplittedChainOperationConstruct($chainProcessors);
    }

    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $returnItems = [];
        foreach ($this->chainProcessors as $chainProcessor) {
            $returnItems[] = $chainProcessor->processItemWithChain($item, 0,  $context);
        }

        // Nothing to process.
        return new MixItem($returnItems);
    }

    public function processStop(StopItem $item, ExecutionContext $context): ItemInterface
    {
        foreach ($this->chainProcessors as $chainProcessor) {
            $result = $chainProcessor->processItemWithChain($item, 0,  $context);

            if ($result !== $item) {
                // Return a new stop item in order to continue flushing out data with stop items.
                $item = new StopItem();
            }
        }

        return $item;
    }

    /**
     * @return ChainProcessor[]
     */
    public function getChainProcessors(): array
    {
        return $this->chainProcessors;
    }
}
