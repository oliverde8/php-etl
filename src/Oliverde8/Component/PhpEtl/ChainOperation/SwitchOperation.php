<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainProcessorInterface;
use Oliverde8\Component\PhpEtl\Expression\Expression;
use Oliverde8\Component\PhpEtl\Expression\ExpressionEvaluator;
use Oliverde8\Component\PhpEtl\Expression\ExpressionEvaluatorInterface;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\OperationConfig\SwitchConfig;
use Oliverde8\Component\RuleEngine\RuleApplier;
use Oliverde8\Component\RuleEngine\RuleConfig\RuleConfigInterface;

class SwitchOperation extends AbstractChainOperation implements DataChainOperationInterface, DetailedObservableOperation, ConfigurableChainOperationInterface, SubChainsAwareOperationInterface
{
    use SplittedChainOperationTrait;

    /** @var array<int, array{rules: RuleConfigInterface|Expression|array, processor: ChainProcessorInterface}> */
    private array $cases = [];

    private ?ChainProcessorInterface $defaultProcessor = null;

    private readonly bool $isolateContext;

    public function __construct(
        ChainBuilderV2 $chainBuilder,
        private readonly RuleApplier $ruleApplier,
        SwitchConfig $config,
        private readonly ExpressionEvaluatorInterface $expressionEvaluator = new ExpressionEvaluator(),
    ) {
        foreach ($config->getCases() as $case) {
            $this->cases[] = [
                'rules' => $case['rules'],
                'processor' => $chainBuilder->createChain($case['then']),
            ];
        }

        if ($config->getDefaultChainConfig() !== null) {
            $this->defaultProcessor = $chainBuilder->createChain($config->getDefaultChainConfig());
        }
        $this->isolateContext = $config->isolateContext;

        $this->onSplittedChainOperationConstruct($this->getChainProcessors());
    }

    #[\Override]
    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $processor = $this->matchCase($item, $context) ?? $this->defaultProcessor;
        if ($processor === null) {
            // No case matched and no default configured, item continues unchanged.
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

    private function matchCase(DataItemInterface $item, ExecutionContext $context): ?ChainProcessorInterface
    {
        foreach ($this->cases as $case) {
            $rules = $case['rules'];

            if ($rules instanceof Expression) {
                $result = $this->expressionEvaluator->evaluate(
                    $rules->expression,
                    ['data' => $item->getData(), 'context' => $context->getParameters()],
                );
            } elseif ($rules instanceof RuleConfigInterface) {
                $resultData = [];
                $result = $this->ruleApplier->applyConfig($item->getData(), $resultData, $rules);
            } else {
                $resultData = [];
                $result = $this->ruleApplier->apply($item->getData(), $resultData, $rules);
            }

            if ($result) {
                return $case['processor'];
            }
        }

        return null;
    }

    /**
     * @return ChainProcessorInterface[]
     */
    #[\Override]
    public function getChainProcessors(): array
    {
        $processors = array_map(static fn(array $case) => $case['processor'], $this->cases);
        if ($this->defaultProcessor !== null) {
            $processors[] = $this->defaultProcessor;
        }

        return $processors;
    }

    public function getConfigurationClass(): string
    {
        return SwitchConfig::class;
    }
}
