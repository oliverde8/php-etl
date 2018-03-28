<?php

namespace Oliverde8\Component\PhpEtl;

use Oliverde8\Component\PhpEtl\Exception\ChainOperationException;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\StopItem;

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
     * @param $context
     * @throws ChainOperationException
     */
    public function process(\Iterator $items, $context);

    /**
     * Process an item, with chains starting at.
     *
     * @param ItemInterface $item
     * @param $startAt
     * @param $context
     *
     * @return mixed|ItemInterface|StopItem
     * @throws ChainOperationException
     */
    public function processItem(ItemInterface $item, $startAt, &$context);
}