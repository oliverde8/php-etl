<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl;

use Oliverde8\Component\PhpEtl\Exception\ChainOperationException;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;

/**
 * Class ChainProcessorInterface
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl
 */
interface ChainProcessorInterface
{
    /**
     * Process items.
     *
     * @param \Iterator $items
     * @param array $parameters
     * @throws ChainOperationException
     */
    public function process(\Iterator $items, array $parameters);

    /**
     * Process an item, with chains starting at.
     */
    public function processItemWithChain(ItemInterface $item, int $startAt, ExecutionContext $context);
}
