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
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\Process\CommandPipeline;
use Oliverde8\Component\PhpEtl\Model\Process\CommandPipelineStreamWrapper;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CommandCsvExtractConfig;

class CommandCsvExtractOperation extends AbstractChainOperation implements DataChainOperationInterface, ConfigurableChainOperationInterface
{
    public function __construct(protected readonly CommandCsvExtractConfig $config)
    {}

    #[\Override]
    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        $filename = $item->getData();
        if (is_array($filename)) {
            $filename = AssociativeArray::getFromKey($filename, $this->config->fileKey);
        }

        $fileSystem = $context->getFileSystem();
        $cwd = is_dir($fileSystem->getRootPath()) ? $fileSystem->getRootPath() : null;
        $pipeline = new CommandPipeline($this->config->commands, $fileSystem->readStream($filename), $cwd, $this->config->idleTimeout);

        $csv = new Csv(
            CommandPipelineStreamWrapper::open($pipeline),
            $this->config->delimiter,
            $this->config->enclosure,
            $this->config->escape,
            $this->config->columns
        );

        return new MixItem([new GroupedItem($this->readRows($pipeline, $csv)), new FileExtractedItem($filename)]);
    }

    private function readRows(CommandPipeline $pipeline, Csv $csv): \Generator
    {
        try {
            foreach ($csv as $row) {
                yield $row;
            }
            $pipeline->close();
        } finally {
            $pipeline->terminate();
        }
    }

    public function getConfigurationClass(): string
    {
        return CommandCsvExtractConfig::class;
    }
}
