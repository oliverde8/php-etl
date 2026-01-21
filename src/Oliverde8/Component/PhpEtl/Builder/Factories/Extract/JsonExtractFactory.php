<?php

namespace Oliverde8\Component\PhpEtl\Builder\Factories\Extract;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\JsonExtractConfig;

class JsonExtractFactory extends AbstractFactory
{
    #[\Override]
    protected function build(string $operation, array $options): ChainOperationInterface
    {
        return $this->create(
            new JsonExtractConfig(
            $options['fileKey'] ?? 'file'
            )
        );
    }
}
