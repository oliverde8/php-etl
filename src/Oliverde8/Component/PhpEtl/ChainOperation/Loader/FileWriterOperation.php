<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation\Loader;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\FileLoadedItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Load\File\FileWriterInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;

/**
 * Class FileWriter
 *
 * @author    de Cramer Oliver<oliverde8@gmail.com>
 * @copyright 2022 Oliverde8
 * @package Oliverde8\Component\PhpEtl\ChainOperation\Loader
 */
class FileWriterOperation extends AbstractChainOperation implements DataChainOperationInterface
{
    /** @var FileWriterInterface */
    protected $writer;

    public function __construct(FileWriterInterface $writer, protected string $fileName)
    {
        $this->writer = $writer;
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function processData(DataItemInterface $item, ExecutionContext $context): DataItemInterface
    {
        $this->writer->write($item->getData());

        return $item;
    }

    public function processStop(StopItem $stopItem, ExecutionContext $context): ItemInterface
    {
        $resource = $this->writer->getResource();

        if(is_resource($resource) && $stopItem->isFinal) {
            $meta_data = stream_get_meta_data($resource);
            $filename = $meta_data["uri"];

            $context->getFileSystem()->writeStream($this->fileName, fopen($filename, 'r'));

            fclose($resource);
            unlink($filename);
        }

        return new MixItem([new FileLoadedItem($this->fileName), $stopItem]);
    }
}
