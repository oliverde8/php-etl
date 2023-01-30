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
    protected string $delimiter;

    protected string $enclosure;

    protected string $escape;

    protected string $fileKey;

    protected bool $scoped;

    public function __construct(string $delimiter, string $enclosure, string $escape, string $fileKey, bool $scoped)
    {
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;
        $this->fileKey = $fileKey;
        $this->scoped = $scoped;
    }


    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $filename = $item->getData();
        if (is_array($filename)) {
            $filename = AssociativeArray::getFromKey($filename, $this->fileKey);
        }

        $fileIterator = new Csv($filename, $this->delimiter, $this->enclosure, $this->escape);
        if ($this->scoped) {
            $context->getFileSystem()->readStream($filename);
        }

        return new MixItem([new GroupedItem($fileIterator), new FileExtractedItem($filename)]);
    }
}