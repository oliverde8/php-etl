<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Model;

use Oliverde8\Component\PhpEtl\ChainOperation\ChainRepeatOperation;
use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\Item\AsyncHttpClientResponseItem;
use Oliverde8\Component\PhpEtl\Item\AsyncItemInterface;
use Oliverde8\Component\PhpEtl\Item\ChainBreakItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;

class RepeatOperationIterator implements \Iterator
{
    protected ?ItemInterface $lastItem = null;
    protected ?int $key = null;

    public function __construct(
        protected readonly ChainProcessor $chainProcessor,
        protected readonly ItemInterface $inputItem,
        protected readonly ExecutionContext $context,
        protected readonly ChainRepeatOperation $operation
    ){}

    public function current(): mixed
    {
        $this->initIterator();
        return $this->lastItem;
    }

    public function next(): void
    {
        if (!$this->valid()) {
            $this->lastItem = new ChainBreakItem();
            return;
        }

        $this->lastItem = $this->chainProcessor->processItemWithChain($this->inputItem, 0, $this->context);

        if (!$this->valid()) {
            $this->lastItem = new ChainBreakItem();
        }
        $this->key++;
    }

    public function key(): mixed
    {
        $this->initIterator();

        return $this->key;
    }

    public function valid(): bool
    {
        if ($this->lastItem) {
            return $this->operation->itemIsValid($this->lastItem, $this->context);
        }
        return true;
    }

    public function rewind(): void {}

    protected function initIterator(): void
    {
        if ($this->lastItem === null) {
            $this->key = 0;
            $this->next();
        }
    }
}
