<?php

namespace Oliverde8\Component\PhpEtl\ChainOperation\Extract;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\FileExtractedItem;
use Oliverde8\Component\PhpEtl\Item\GroupedItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\JsonExtractConfig;

class JsonExtractOperation extends AbstractChainOperation implements DataChainOperationInterface, ConfigurableChainOperationInterface
{
    public function __construct(private readonly JsonExtractConfig $config)
    {}

    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $filename = $item->getData();
        if (is_array($filename)) {
            $filename = AssociativeArray::getFromKey($filename, $this->config->fileKey);
        }

        $data = json_decode($context->getFileSystem()->read($filename), true);

        return new MixItem([new GroupedItem(new \ArrayIterator($data)), new FileExtractedItem($filename)]);
    }
}
