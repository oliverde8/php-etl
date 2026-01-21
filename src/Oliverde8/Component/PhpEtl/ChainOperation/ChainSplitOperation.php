<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\State\OperationState;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainSplitConfig;

/**
 * Class ChainSplitOperation
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\ChainOperation
 */
class ChainSplitOperation extends AbstractChainOperation implements DataChainOperationInterface, DetailedObservableOperation, ConfigurableChainOperationInterface
{
    use SplittedChainOperationTrait;

    /**
     * @var ChainProcessor[]
     */
    protected array $chainProcessors = [];


    public function __construct(ChainBuilderV2 $chainProcessors, ChainSplitConfig $config)
    {
        foreach ($config->getChainConfigs() as $chainConfig) {
            $this->chainProcessors[] = $chainProcessors->createChain($chainConfig);
        }
        $this->onSplittedChainOperationConstruct($this->chainProcessors);
    }

    #[\Override]
    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        foreach ($this->chainProcessors as $chainProcessor) {
            foreach ($chainProcessor->processGenerator($item, $context, withStop: false) as $newItem) {}
        }

        // Nothing to process.
        return $item;
    }

    public function processStop(StopItem $item, ExecutionContext $context): ItemInterface
    {
        foreach ($this->chainProcessors as $chainProcessor) {
            foreach ($chainProcessor->processGenerator($item, $context) as $newItem) {}
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
