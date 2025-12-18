<?php

namespace Oliverde8\Component\PhpEtl\ChainOperation\Transformer;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\ChainBreakItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\RuleEngine\RuleApplier;

class FilterDataOperation extends AbstractChainOperation implements DataChainOperationInterface, ConfigurableChainOperationInterface
{

    public function __construct(
        private readonly RuleApplier $ruleApplier,
        private readonly FilterDataConfig $config
    ) {}


    #[\Override]
    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $data = $item->getData();

        $resultData = [];
        $result = $this->ruleApplier->apply($data, $resultData, $this->config->rules);

        if (($this->config->negate && $result == false) || (!$this->config->negate && $result == true)) {
            return $item;
        }

        return new ChainBreakItem();
    }
}