<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainMergeConfig;

/**
 * Class ChainMergeOperation
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\ChainOperation
 */
class ChainMergeOperation extends AbstractChainOperation implements DataChainOperationInterface, DetailedObservableOperation, ConfigurableChainOperationInterface
{
    use SplittedChainOperationTrait;

    /**
     * @var ChainProcessor[]
     */
    private array $chainProcessors = [];

    public function __construct(ChainBuilderV2 $chainBuilder, ChainMergeConfig $config)
    {
        foreach ($config->getChainConfigs() as $chainConfig) {
            $this->chainProcessors[] = $chainBuilder->createChain($chainConfig);
        }
        $this->onSplittedChainOperationConstruct($this->chainProcessors);
    }

    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $returnItems = [];
        foreach ($this->chainProcessors as $chainProcessor) {
            foreach ($chainProcessor->processGenerator($item, $context, withStop: false) as $newItem) {
                $returnItems[] = $newItem;
            }
        }

        // Return all items merged together.
        return new MixItem($returnItems);
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

    public function getConfigurationClass(): string
    {
        return ChainMergeConfig::class;
    }
}
