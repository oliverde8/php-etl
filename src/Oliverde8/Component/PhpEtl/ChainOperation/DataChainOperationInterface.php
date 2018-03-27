<?php

namespace Oliverde8\Component\PhpEtl\ChainOperation;

use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;

/**
 * Class DataChainOperationInterface
 *
 * @author    de Cramer Oliver<oliverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\ChainOperation
 */
interface DataChainOperationInterface
{
    /**
     * Process DataItems.
     *
     * @param DataItemInterface $item
     * @param array $context
     *
     * @return ItemInterface
     */
    public function processData(DataItemInterface $item, array &$context);
}