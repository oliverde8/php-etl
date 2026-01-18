<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\GroupedItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainRepeatConfig;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ChainRepeatOperation extends AbstractChainOperation implements DetailedObservableOperation, ConfigurableChainOperationInterface
{
    use SplittedChainOperationTrait;

    private ExpressionLanguage $expressionLanguage;
    private ChainProcessor $chainProcessor;
    private bool $allowAsynchronous;
    private string $validationExpression;

    public function __construct(ChainBuilderV2 $chainBuilder, ChainRepeatConfig $config)
    {
        $this->chainProcessor = $chainBuilder->createChain($config->getChainConfig());
        $this->validationExpression = $config->validationExpression;
        $this->allowAsynchronous = $config->allowAsynchronous;

        $this->onSplittedChainOperationConstruct([$this->chainProcessor]);
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public function processData(DataItemInterface $inputItem, ExecutionContext $context): ItemInterface
    {
        // Nothing to process.
        return new GroupedItem($this->repeatOnItem($inputItem, $context));
    }

    public function repeatOnItem(DataItemInterface $inputItem, ExecutionContext $context): \Generator
    {
        $invalidItem = false;
        do {
            $item = null;
            foreach ($this->chainProcessor->processGenerator($inputItem, $context, withStop: false, allowAsynchronous: $this->allowAsynchronous) as $item) {
                if ($this->itemIsValid($item, $context)) {
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
            $values = ['data' => $item->getData(), 'context' => $context];
            return $this->expressionLanguage->evaluate($this->validationExpression, $values);
        }

        // If not a data, then it's valid.
        return true;
    }

    public function getConfigurationClass(): string
    {
        return ChainRepeatConfig::class;
    }
}
