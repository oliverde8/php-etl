<?php

namespace Oliverde8\Component\PhpEtl\Item;

class MixItem implements ItemInterface
{
    public function __construct(private readonly array $items)
    {
    }

    public function getItems(): array
    {
        return $this->items;
    }
}