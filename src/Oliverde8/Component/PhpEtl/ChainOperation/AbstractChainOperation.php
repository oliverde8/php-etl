<?php

namespace Oliverde8\Component\PhpEtl\ChainOperation;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;

/**
 * Class AbstractChainOperation
 *
 * @author    de Cramer Oliver<oliverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\ChainOperation
 */
class AbstractChainOperation implements ChainOperationInterface
{
    /**
     * @inheritdoc
     */
    public function process(ItemInterface $item, array &$context): ItemInterface
    {
        $method = 'process' . ucfirst($item->getMethod());

        if (method_exists($this, $method)) {
            return $this->$method($item, $context);
        }

        return $item;
    }
}