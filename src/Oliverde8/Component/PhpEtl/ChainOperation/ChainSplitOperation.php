<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainProcessorInterface;
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
class ChainSplitOperation extends AbstractChainOperation implements DataChainOperationInterface, DetailedObservableOperation, ConfigurableChainOperationInterface, SubChainsAwareOperationInterface
{
    use SplittedChainOperationTrait;

    /**
     * @var ChainProcessorInterface[]
     */
    protected array $chainProcessors = [];

    private readonly bool $isolateContext;

    public function __construct(ChainBuilderV2 $chainProcessors, ChainSplitConfig $config)
    {
        foreach ($config->getChainConfigs() as $chainConfig) {
            $this->chainProcessors[] = $chainProcessors->createChain($chainConfig);
        }
        $this->isolateContext = $config->isolateContext;
        $this->onSplittedChainOperationConstruct($this->chainProcessors);
    }

    #[\Override]
    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        foreach ($this->chainProcessors as $chainProcessor) {
            $branchContext = $this->isolateContext ? clone $context : $context;
            foreach ($chainProcessor->processGenerator($item, $branchContext, withStop: false) as $newItem) {}
        }

        // Nothing to process.
        return $item;
    }

    public function processStop(StopItem $item, ExecutionContext $context): ItemInterface
    {
        foreach ($this->chainProcessors as $chainProcessor) {
            $branchContext = $this->isolateContext ? clone $context : $context;
            foreach ($chainProcessor->processGenerator($item, $branchContext) as $newItem) {}
        }

        return $item;
    }

    /**
     * @return ChainProcessorInterface[]
     */
    #[\Override]
    public function getChainProcessors(): array
    {
        return $this->chainProcessors;
    }
}
