<?php

namespace Oliverde8\Component\PhpEtl\ChainOperation\Transformer;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Exception\ChainOperationException;
use Oliverde8\Component\PhpEtl\Item\ChainBreakItem;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SplitItemConfig;

class SplitItemOperation extends AbstractChainOperation implements DataChainOperationInterface, ConfigurableChainOperationInterface
{
    public function __construct(protected readonly SplitItemConfig $config)
    {
    }

    /**
     * @throws ChainOperationException
     */
    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        if ($this->config->singleElement) {
            $data = AssociativeArray::getFromKey($item->getData(), $this->config->keys[0], new ChainBreakItem());
            if ($data instanceof ItemInterface) {
                return $data;
            }

            return $this->createItem($data, $item->getData());
        }

        $newItemData = [];
        foreach ($this->config->keys as $key) {
            $newItemData[] = AssociativeArray::getFromKey($item->getData(), $key, []);
        }

        return $this->createItem($newItemData);
    }

    protected function createItem($itemData, $fullData = []): ItemInterface
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
                if ($this->config->keyName) {
                    AssociativeArray::setFromKey($dataItem, $this->config->keyName, $datum);
                } else {
                    $dataItem = $datum;
                }

                foreach ($this->config->duplicateKeys as $keyStore => $keyFetch) {
                    AssociativeArray::setFromKey($dataItem, $keyStore, AssociativeArray::getFromKey($fullData, $keyFetch));
                }

                if ($this->config->keepKeys) {
                    $dataItem = ['key' => $datumKey, 'value' => $dataItem];
                }
                $items[] = new DataItem($dataItem);
            }
        }

        return new MixItem($items);
    }
}
