<?php

namespace Oliverde8\Component\PhpEtl\Item;

interface AsyncItemInterface extends ItemInterface
{
    public function isRunning(): bool;

    public function getItem(): ItemInterface;
}