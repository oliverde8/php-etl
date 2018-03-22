<?php

namespace Oliverde8\Component\PhpEtl\Item;

/**
 * Class DataItemInterface
 *
 * @author    de Cramer Oliver<oliverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\Item
 */
interface DataItemInterface extends ItemInterface
{
    const SIGNAL_DATA = 'data';

    public function getData();
}