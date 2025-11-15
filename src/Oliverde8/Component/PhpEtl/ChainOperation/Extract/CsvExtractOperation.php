<?php

namespace Oliverde8\Component\PhpEtl\ChainOperation\Extract;

use oliverde8\AssociativeArraySimplified\AssociativeArray;
use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Extract\File\Csv;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\FileExtractedItem;
use Oliverde8\Component\PhpEtl\Item\GroupedItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;

class CsvExtractOperation extends AbstractChainOperation implements DataChainOperationInterface, ConfigurableChainOperationInterface
{

    public function __construct(protected readonly CsvExtractConfig $config)
    {}

    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $filename = $item->getData();
        if (is_array($filename)) {
            $filename = AssociativeArray::getFromKey($filename, $this->config->fileKey);
        }

        $fileIterator = new Csv($context->getFileSystem()->readStream($filename), $this->config->delimiter, $this->config->enclosure, $this->config->escape);

        return new MixItem([new GroupedItem($fileIterator), new FileExtractedItem($filename)]);
    }

    public function getConfigurationClass(): string
    {
        return CsvExtractConfig::class;
    }
}
