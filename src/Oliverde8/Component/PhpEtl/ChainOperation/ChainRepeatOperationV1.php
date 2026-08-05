<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainProcessorInterface;
use Oliverde8\Component\PhpEtl\Expression\ExpressionEvaluator;

class ChainRepeatOperationV1 extends ChainRepeatOperation
{
    use SplittedChainOperationTrait;

    public function __construct(
        protected ChainProcessorInterface $chainProcessor,
        protected string $validationExpression,
        protected bool $allowAsynchronous = false,
    ) {
        $this->onSplittedChainOperationConstruct([$chainProcessor]);
        $this->expressionEvaluator = new ExpressionEvaluator();
    }
}