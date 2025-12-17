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
    public function __construct(protected bool $singleElement, protected array $keys, protected bool $keepKeys, protected ?string $keyName = null, protected array $duplicateKeys = [])
    {
    }

    /**
     * @throws ChainOperationException
     */
    #[\Override]
    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        if ($this->singleElement) {
            $data = AssociativeArray::getFromKey($item->getData(), $this->keys[0], new ChainBreakItem());
            if ($data instanceof ItemInterface) {
                return $data;
            }

            return $this->createItem($data, $item->getData());
        }

        $newItemData = [];
        foreach ($this->keys as $key) {
            $newItemData[] = AssociativeArray::getFromKey($item->getData(), $key, []);
        }

        return $this->createItem($newItemData);
    }

    protected function createItem($itemData, $fullData): ItemInterface
    {
        if (!is_array($itemData)) {
            throw new ChainOperationException(sprintf('Split operation expects an array to split; "%s', gettype($itemData)));
        }

        $items = [];
        foreach ($itemData as $datumKey => $datum) {
            if ($datum instanceof ItemInterface) {
                $items[] = $datum;
            } else {
                $dataItem = [];
                if ($this->keyName) {
                    AssociativeArray::setFromKey($dataItem, $this->keyName, $datum);
                } else {
                    $dataItem = $datum;
                }

                foreach ($this->duplicateKeys as $keyStore => $keyFetch) {
                    AssociativeArray::setFromKey($dataItem, $keyStore, AssociativeArray::getFromKey($fullData, $keyFetch));
                }

                if ($this->keepKeys) {
                    $dataItem = ['key' => $datumKey, 'value' => $dataItem];
                }
                $items[] = new DataItem($dataItem);
            }
        }

        return new MixItem($items);
    }
}
