<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation\Transformer;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\ExternalFileItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\ExternalFileProcessorConfig;

class ExternalFileProcessorOperation extends AbstractChainOperation implements ConfigurableChainOperationInterface
{
    public function __construct(private readonly ExternalFileProcessorConfig $config)
    {}

    public function processFile(ExternalFileItem $item, ExecutionContext $context): ItemInterface
    {
        $externalFilePath = $item->getFilePath();
        $externalDir = dirname($externalFilePath);
        $fileName = basename($externalFilePath);
        $externalFileSystem = $item->getFileSystem();
        $localFileSystem = $context->getFileSystem();

        if ($item->getState() == ExternalFileItem::STATE_NEW) {
            // Move file to prevent it to be processed by another process.
            $externalFileSystem->createDirectory($externalDir . "/processing");
            $externalFileSystem->move($externalFilePath, $externalDir . "/processing/" . $fileName);

            $localFileSystem->writeStream($fileName, $externalFileSystem->readStream($externalDir . "/processing/" . $fileName));

            $item->setState(ExternalFileItem::STATE_PROCESSING);
            return new MixItem([new DataItem($fileName), $item]);
        } else {
            $externalFileSystem->createDirectory($externalDir . "/processed");
            $externalFileSystem->move($externalDir . "/processing/" . $fileName, $externalDir . "/processed/" . $fileName);

            $item->setState(ExternalFileItem::STATE_PROCESSED);
            return $item;
        }
    }
}