<?php

namespace Oliverde8\Component\PhpEtl\Tests\Item;

use Oliverde8\Component\PhpEtl\Item\AsyncItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;

class TestAsyncItem implements AsyncItemInterface
{
    private ItemInterface $item;

    private int $runTime;

    private int $startTime;

    public function __construct(ItemInterface $item, int $runTime)
    {
        $this->item = $item;
        $this->runTime = $runTime;

        $this->startTime = time();
    }


    public function isRunning(): bool
    {
        return $this->startTime + $this->runTime > time();
    }

    public function getItem(): ItemInterface
    {
        return $this->item;
    }
}