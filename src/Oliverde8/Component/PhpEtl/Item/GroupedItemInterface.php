<?php


namespace Oliverde8\Component\PhpEtl\Item;


/**
 * Interface GroupedItemInterface
 *
 * @author    de Cramer Oliver<oliverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\Item
 */
interface GroupedItemInterface extends ItemInterface
{
    public function getIterator() : \Iterator;
}