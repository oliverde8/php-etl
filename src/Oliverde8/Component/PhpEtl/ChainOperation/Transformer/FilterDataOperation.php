<?php

namespace Oliverde8\Component\PhpEtl\ChainOperation\Transformer;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Expression\Expression;
use Oliverde8\Component\PhpEtl\Expression\ExpressionEvaluator;
use Oliverde8\Component\PhpEtl\Expression\ExpressionEvaluatorInterface;
use Oliverde8\Component\PhpEtl\Item\ChainBreakItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\RuleEngine\RuleApplier;
use Oliverde8\Component\RuleEngine\RuleConfig\RuleConfigInterface;

class FilterDataOperation extends AbstractChainOperation implements DataChainOperationInterface, ConfigurableChainOperationInterface
{

    public function __construct(
        private readonly RuleApplier $ruleApplier,
        private readonly FilterDataConfig $config,
        private readonly ExpressionEvaluatorInterface $expressionEvaluator = new ExpressionEvaluator(),
    ) {}


    #[\Override]
    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $data = $item->getData();
        $rules = $this->config->rules;

        if ($rules instanceof Expression) {
            $result = $this->expressionEvaluator->evaluate($rules->expression, ['data' => $data, 'context' => $context->getParameters()]);
        } elseif ($rules instanceof RuleConfigInterface) {
            $resultData = [];
            $result = $this->ruleApplier->applyConfig($data, $resultData, $rules);
        } else {
            $resultData = [];
            $result = $this->ruleApplier->apply($data, $resultData, $rules);
        }

        if (($this->config->negate && $result == false) || (!$this->config->negate && $result == true)) {
            return $item;
        }

        return new ChainBreakItem();
    }
}
