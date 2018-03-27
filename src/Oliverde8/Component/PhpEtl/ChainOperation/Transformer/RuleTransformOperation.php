<?php

namespace Oliverde8\Component\PhpEtl\ChainOperation\Transformer;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\RuleEngine\RuleApplier;

/**
 * Class RuleTransformOperation
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\ChainOperation\Transformer
 */
class RuleTransformOperation extends AbstractChainOperation implements DataChainOperationInterface
{
    /** @var RuleApplier */
    protected $ruleApplier;

    /** @var array */
    protected $rules;

    /** @var boolean */
    protected $add;

    /**
     * RuleTransformOperation constructor.
     *
     * @param RuleApplier $ruleApplier
     * @param array $rules
     * @param boolean $add
     */
    public function __construct(RuleApplier $ruleApplier, array $rules, $add)
    {
        $this->ruleApplier = $ruleApplier;
        $this->rules = $rules;
        $this->add = $add;
    }

    /**
     * @param DataItemInterface $item
     * @param array $context
     *
     * @return ItemInterface
     * @throws \Oliverde8\Component\RuleEngine\Exceptions\RuleException
     * @throws \Oliverde8\Component\RuleEngine\Exceptions\UnknownRuleException
     */
    public function processData(DataItemInterface $item, array &$context)
    {
        $data = $item->getData();
        $newData = [];

        // We add data and don't send new data.
        if ($this->add) {
            $newData = $data;
        }

        foreach ($this->rules as $column => $rule) {
            $newData[$column] = $this->ruleApplier->apply($data, $newData, $rule['rules'], []);
        }

        return new DataItem($newData);
    }
}