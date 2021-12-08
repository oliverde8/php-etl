<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Item;

/**
 * Class GroupedItem
 *
 * @author    de Cramer Oliver<oliverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\Item
 */
class GroupedItem implements GroupedItemInterface
{

    protected \Iterator $iterator;

    /**
     * GroupedItem constructor.
     *
     * @param $iterator
     */
    public function __construct(\Iterator $iterator)
    {
        $this->iterator = $iterator;
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): \Iterator
    {
        return $this->iterator;
    }

    public function getMethod(): string
    {
        return DataItemInterface::SIGNAL_DATA;
    }
}
