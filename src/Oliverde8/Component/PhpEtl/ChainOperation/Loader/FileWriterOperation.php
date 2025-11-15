<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation\Loader;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\FileLoadedItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Load\File\FileWriterInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\FileWriterConfigInterface;

class FileWriterOperation extends AbstractChainOperation implements DataChainOperationInterface, ConfigurableChainOperationInterface
{
    private ?FileWriterInterface $writer = null;

    public function __construct(private readonly FileWriterConfigInterface $config)
    {}

    /**
     * @inheritdoc
     */
    public function processData(DataItemInterface $item, ExecutionContext $context): DataItemInterface
    {
        if ($this->writer === null) {
            $this->writer = $this->config->getFile();
        }

        $this->writer->write($item->getData());

        return $item;
    }

    public function processStop(StopItem $stopItem, ExecutionContext $context): ItemInterface
    {
        if ($this->writer === null) {
            $this->writer = $this->config->getFile();
        }

        $resource = $this->writer->getResource();

        if(is_resource($resource) && $stopItem->isFinal) {
            $meta_data = stream_get_meta_data($resource);
            $filename = $meta_data["uri"];

            $context->getFileSystem()->writeStream($this->config->getFileName(), fopen($filename, 'r'));

            fclose($resource);
            unlink($filename);
        }

        return new MixItem([new FileLoadedItem($this->config->getFileName(),), $stopItem]);
    }
}
