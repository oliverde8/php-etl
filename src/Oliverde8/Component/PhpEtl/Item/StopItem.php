<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Item;

/**
 * Class StopItemInterface
 *
 * @author    de Cramer Oliver<oliverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\Item
 */
class StopItem implements ItemInterface
{
    public function __construct(public readonly bool $isFinal = false){}
}
