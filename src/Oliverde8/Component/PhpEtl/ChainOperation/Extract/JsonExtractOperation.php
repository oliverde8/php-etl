<?php

namespace Oliverde8\Component\PhpEtl\ChainOperation\Extract;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\FileExtractedItem;
use Oliverde8\Component\PhpEtl\Item\GroupedItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;

class JsonExtractOperation extends AbstractChainOperation implements DataChainOperationInterface
{
    public function __construct(protected string $fileKey, protected bool $scoped)
    {
    }

    #[\Override]
    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $filename = $item->getData();
        if (is_array($filename)) {
            $filename = AssociativeArray::getFromKey($filename, $this->fileKey);
        }

        if ($this->scoped) {
            $data = json_decode($context->getFileSystem()->read($filename), true);
        } else {
            $data = json_decode(file_get_contents($filename), true);
        }

        return new MixItem([new GroupedItem(new \ArrayIterator($data)), new FileExtractedItem($item->getData())]);
    }
}
