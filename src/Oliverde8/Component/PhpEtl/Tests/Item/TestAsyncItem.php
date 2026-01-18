<?php

namespace Oliverde8\Component\PhpEtl\Tests\Item;

use Oliverde8\Component\PhpEtl\Item\AsyncItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;

class TestAsyncItem implements AsyncItemInterface
{
    private readonly int $startTime;

    public function __construct(private readonly ItemInterface $item, private readonly int $runTime)
    {
        $this->startTime = time();
    }


    #[\Override]
    public function isRunning(): bool
    {
        return $this->startTime + $this->runTime > time();
    }

    #[\Override]
    public function getItem(): ItemInterface
    {
        return $this->item;
    }
}