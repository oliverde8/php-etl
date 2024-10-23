<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\GroupedItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\RepeatOperationIterator;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ChainRepeatOperation extends AbstractChainOperation implements DetailedObservableOperation
{
    use SplittedChainOperationTrait;

    private ExpressionLanguage $expressionLanguage;

    public function __construct(
        protected readonly ChainProcessor $chainProcessor,
        protected readonly string $validationExpression
    ) {
        $this->onSplittedChainOperationConstruct([$chainProcessor]);
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public function processData(DataItemInterface $inputItem, ExecutionContext $context): ItemInterface
    {
        // Nothing to process.
        return new GroupedItem(new RepeatOperationIterator(
            $this->chainProcessor,
            $inputItem,
            $context,
            $this
        ));
    }

    public function itemIsValid(ItemInterface $item, ExecutionContext $context): bool
    {
        if ($item instanceof DataItemInterface) {
            $values = ['data' => $item->getData(), 'context' => $context];
            return $this->expressionLanguage->evaluate($this->validationExpression, $values);
        }
        
        return false;
    }
}
