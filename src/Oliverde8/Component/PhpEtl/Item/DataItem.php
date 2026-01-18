<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Item;

/**
 * Class DataItem
 *
 * @author    de Cramer Oliver<oliverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\Item
 */
class DataItem implements DataItemInterface
{
    /**
     * DataItem constructor.
     */
    public function __construct(protected mixed $data)
    {
    }

    #[\Override]
    public function getData()
    {
       return $this->data;
    }
}
