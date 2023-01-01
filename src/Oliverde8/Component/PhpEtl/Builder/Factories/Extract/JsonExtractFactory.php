<?php

namespace Oliverde8\Component\PhpEtl\Builder\Factories\Extract;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;

class JsonExtractFactory extends AbstractFactory
{
    protected function build(string $operation, array $options): ChainOperationInterface
    {
        return $this->create($options['fileKey'] ?? 'file', $options['scoped'] ?? false);
    }
}
