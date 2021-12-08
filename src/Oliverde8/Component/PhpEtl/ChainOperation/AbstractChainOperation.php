<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation;

use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;

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
    public function process(ItemInterface $item, ExecutionContext $context): ItemInterface
    {
        // TODO Make this more intelligent with a small cache not to make it slow.
        $method = 'process' . ucfirst($item->getMethod());
        if (method_exists($this, $method)) {
            return $this->$method($item, $context);
        }

        return $item;
    }

    protected function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        return $item;
    }

    protected function processStop(StopItem $item, ExecutionContext $context): ItemInterface
    {
        return $item;
    }
}
