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

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        return $this->iterator;
    }

    public function getMethod()
    {
        return DataItemInterface::SIGNAL_DATA;
    }
}
