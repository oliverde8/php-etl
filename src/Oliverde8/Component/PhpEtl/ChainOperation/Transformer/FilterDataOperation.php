<?php

namespace Oliverde8\Component\PhpEtl\ChainOperation\Transformer;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\ChainBreakItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\RuleEngine\RuleApplier;

class FilterDataOperation extends AbstractChainOperation implements DataChainOperationInterface
{
    public function __construct(protected RuleApplier $ruleApplier, protected array $rule, protected bool $negate)
    {
    }


    #[\Override]
    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $data = $item->getData();

        $resultData = [];
        $result = $this->ruleApplier->apply($data, $resultData, $this->rule);

        if (($this->negate && $result == false) || (!$this->negate && $result == true)) {
            return $item;
        }

        return new ChainBreakItem();
    }
}