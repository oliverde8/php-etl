<?php

namespace Oliverde8\Component\PhpEtl\Item;

class MixItem implements ItemInterface
{
    private array $items;

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function getItems(): array
    {
        return $this->items;
    }
}