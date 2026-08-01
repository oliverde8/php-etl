<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainProcessorInterface;
use Oliverde8\Component\PhpEtl\Expression\ExpressionEvaluator;
use Oliverde8\Component\PhpEtl\Expression\ExpressionEvaluatorInterface;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\GroupedItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainRepeatConfig;

class ChainRepeatOperation extends AbstractChainOperation implements DetailedObservableOperation, ConfigurableChainOperationInterface, SubChainsAwareOperationInterface
{
    use SplittedChainOperationTrait;

    protected ChainProcessorInterface $chainProcessor;
    protected bool $allowAsynchronous;
    protected string $validationExpression;
    private readonly bool $isolateContext;

    public function __construct(
        ChainBuilderV2 $chainBuilder,
        ChainRepeatConfig $config,
        protected ExpressionEvaluatorInterface $expressionEvaluator = new ExpressionEvaluator(),
    ) {
        $this->chainProcessor = $chainBuilder->createChain($config->getChainConfig());
        $this->validationExpression = $config->validationExpression;
        $this->allowAsynchronous = $config->allowAsynchronous;
        $this->isolateContext = $config->isolateContext;

        $this->onSplittedChainOperationConstruct([$this->chainProcessor]);
    }

    public function processData(DataItemInterface $inputItem, ExecutionContext $context): ItemInterface
    {
        // Nothing to process.
        return new GroupedItem($this->repeatOnItem($inputItem, $context));
    }

    public function repeatOnItem(DataItemInterface $inputItem, ExecutionContext $context): \Generator
    {
        $branchContext = $this->isolateContext ? clone $context : $context;

        $invalidItem = false;
        do {
            $item = null;
            foreach ($this->chainProcessor->processGenerator($inputItem, $branchContext, withStop: false, allowAsynchronous: $this->allowAsynchronous) as $item) {
                if ($this->itemIsValid($item, $branchContext)) {
                    yield $item;
                } else {
                    $invalidItem = true;
                }
            }
        } while ($item && !$invalidItem);
    }

    public function itemIsValid(ItemInterface $item, ExecutionContext $context): bool
    {
        if ($item instanceof DataItemInterface) {
            $values = ['data' => $item->getData(), 'context' => $context->getParameters()];
            return $this->expressionEvaluator->evaluate($this->validationExpression, $values);
        }

        // If not a data, then it's valid.
        return true;
    }

    public function getConfigurationClass(): string
    {
        return ChainRepeatConfig::class;
    }

    /**
     * @return ChainProcessorInterface[]
     */
    #[\Override]
    public function getChainProcessors(): array
    {
        return [$this->chainProcessor];
    }
}
