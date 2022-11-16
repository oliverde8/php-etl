<?php

namespace Oliverde8\Component\PhpEtl\Builder\Factories\Extract;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

class CsvExtractFactory extends AbstractFactory
{
    protected function build(string $operation, array $options): ChainOperationInterface
    {
        return $this->create(
            $options['delimiter'] ?? ";",
            $options['enclosure'] ?? '"',
            $options['escape'] ?? '\\',
            $options['fileKey'] ?? 'file',
        );
    }
}