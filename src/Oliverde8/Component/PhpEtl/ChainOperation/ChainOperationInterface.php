<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation;

use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;

/**
 * Class ChainLinkInterface
 *
 * @author    de Cramer Oliver<oliverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\ChainLink
 */
interface ChainOperationInterface
{
    /**
     * Generic processor for all type of Items.
     *
     * @param ItemInterface $item
     * @param array $context
     *
     * @return ItemInterface
     */
    public function process(ItemInterface $item, ExecutionContext $context);
}
