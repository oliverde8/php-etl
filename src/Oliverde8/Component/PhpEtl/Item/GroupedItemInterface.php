<?php

declare(strict_types=1);

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
    /**
     * Get iterator returning list of grouped data that needs to be processed individually.
     *
     * @return mixed
     */
    public function getIterator() : \Iterator;
}