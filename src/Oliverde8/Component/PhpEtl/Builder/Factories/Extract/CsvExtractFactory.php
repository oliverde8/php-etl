<?php

namespace Oliverde8\Component\PhpEtl\Builder\Factories\Extract;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;

class CsvExtractFactory extends AbstractFactory
{
    #[\Override]
    protected function build(string $operation, array $options): ChainOperationInterface
    {
        return $this->create(
            new CsvExtractConfig(
                $options['delimiter'] ?? ";",
                $options['enclosure'] ?? '"',
                $options['escape'] ?? '\\',
                $options['fileKey'] ?? 'file'
            )
        );
    }
}