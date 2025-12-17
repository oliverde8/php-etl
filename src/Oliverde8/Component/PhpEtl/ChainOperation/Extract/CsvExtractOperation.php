<?php

namespace Oliverde8\Component\PhpEtl\ChainOperation\Extract;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Extract\File\Csv;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\FileExtractedItem;
use Oliverde8\Component\PhpEtl\Item\GroupedItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;

class CsvExtractOperation extends AbstractChainOperation implements DataChainOperationInterface
{
    public function __construct(protected string $delimiter, protected string $enclosure, protected string $escape, protected string $fileKey, protected bool $scoped)
    {
    }


    #[\Override]
    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $filename = $item->getData();
        if (is_array($filename)) {
            $filename = AssociativeArray::getFromKey($filename, $this->fileKey);
        }

        $fileIterator = new Csv($context->getFileSystem()->readStream($filename), $this->delimiter, $this->enclosure, $this->escape);

        return new MixItem([new GroupedItem($fileIterator), new FileExtractedItem($filename)]);
    }
}