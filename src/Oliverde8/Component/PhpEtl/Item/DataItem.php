<?php

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
     * @var mixed
     */
    protected $data;

    /**
     * DataItem constructor.
     *
     * @param mixed $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @inheritdoc
     */
    public function getMethod()
    {
        return 'data';
    }

    public function getData()
    {
       return $this->data;
    }
}
