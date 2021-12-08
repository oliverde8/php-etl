<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation;

use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;

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
     * @param ExecutionContext $context
     *
     * @return ItemInterface
     */
    public function processData(DataItemInterface $item, ExecutionContext $context);
}