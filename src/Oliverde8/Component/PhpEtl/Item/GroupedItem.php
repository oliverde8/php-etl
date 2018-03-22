<?php

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

    protected $iterator;

    /**
     * GroupedItem constructor.
     *
     * @param $iterator
     */
    public function __construct($iterator)
    {
        $this->iterator = $iterator;
    }


    public function getIterator(): \Iterator
    {
        return $this->iterator;
    }

    public function getSignal(): string
    {
        return DataItemInterface::SIGNAL_DATA;
    }
}