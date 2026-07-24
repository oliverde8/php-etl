<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainProcessorInterface;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\OperationConfig\IfConfig;
use Oliverde8\Component\RuleEngine\RuleApplier;

class IfOperation extends AbstractChainOperation implements DataChainOperationInterface, DetailedObservableOperation, ConfigurableChainOperationInterface, SubChainsAwareOperationInterface
{
    use SplittedChainOperationTrait;

    private ChainProcessorInterface $thenProcessor;
    private ?ChainProcessorInterface $elseProcessor = null;
    private readonly bool $isolateContext;

    public function __construct(
        ChainBuilderV2 $chainBuilder,
        private readonly RuleApplier $ruleApplier,
        private readonly IfConfig $config,
    ) {
        $this->thenProcessor = $chainBuilder->createChain($config->getThenChainConfig());
        if ($config->getElseChainConfig() !== null) {
            $this->elseProcessor = $chainBuilder->createChain($config->getElseChainConfig());
        }
        $this->isolateContext = $config->isolateContext;

        $this->onSplittedChainOperationConstruct($this->getChainProcessors());
    }

    #[\Override]
    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $resultData = [];
        $result = $this->ruleApplier->apply($item->getData(), $resultData, $this->config->rules);
        $conditionMet = $this->config->negate ? !$result : (bool) $result;

        $processor = $conditionMet ? $this->thenProcessor : $this->elseProcessor;
        if ($processor === null) {
            // No branch configured for this outcome, item continues unchanged.
            return $item;
        }

        $branchContext = $this->isolateContext ? clone $context : $context;

        $results = [];
        foreach ($processor->processGenerator($item, $branchContext, withStop: false) as $newItem) {
            $results[] = $newItem;
        }

        return new MixItem($results);
    }

    public function processStop(StopItem $item, ExecutionContext $context): ItemInterface
    {
        foreach ($this->getChainProcessors() as $processor) {
            $branchContext = $this->isolateContext ? clone $context : $context;
            foreach ($processor->processGenerator($item, $branchContext) as $newItem) {}
        }

        return $item;
    }

    /**
     * @return ChainProcessorInterface[]
     */
    #[\Override]
    public function getChainProcessors(): array
    {
        $processors = [$this->thenProcessor];
        if ($this->elseProcessor !== null) {
            $processors[] = $this->elseProcessor;
        }

        return $processors;
    }
}
