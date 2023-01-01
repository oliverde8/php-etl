<?php

namespace Oliverde8\Component\PhpEtl\ChainOperation\Transformer;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Exception\ChainOperationException;
use Oliverde8\Component\PhpEtl\Item\ChainBreakItem;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;

class SplitItemOperation extends AbstractChainOperation implements DataChainOperationInterface
{
    protected bool $singleElement;

    protected array $keys;

    /**
     * @param bool $singleElement
     * @param array $keys
     */
    public function __construct(bool $singleElement, array $keys)
    {
        $this->singleElement = $singleElement;
        $this->keys = $keys;
    }

    /**
     * @throws ChainOperationException
     */
    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        if ($this->singleElement) {
            $data = AssociativeArray::getFromKey($item->getData(), $this->keys[0], new ChainBreakItem());

            if ($data instanceof ItemInterface) {
                return $data;
            }

            return $this->createItem($data);
        }

        $newItemData = [];
        foreach ($this->keys as $key) {
            $newItemData[] = AssociativeArray::getFromKey($item->getData(), $key, []);
        }

        return $this->createItem($newItemData);
    }

    protected function createItem($data): ItemInterface
    {
        if (!is_array($data)) {
            throw new ChainOperationException(sprintf('Split operation expects an array to split; "%s', gettype($data)));
        }

        $items = [];
        foreach ($data as $datum) {
            if ($datum instanceof ItemInterface) {
                $items[] = $datum;
            } else {
                $items[] = new DataItem($datum);
            }
        }

        return new MixItem($items);
    }
}