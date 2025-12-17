<?php

namespace Oliverde8\Component\PhpEtl\Tests\Fixtures;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\OperationConfigInterface;

class TestOperationWithDefaults extends AbstractChainOperation implements ConfigurableChainOperationInterface
{
    public function __construct(
        private readonly OperationConfigInterface $config,
        private readonly string $optionalParam = 'default_value'
    ) {
    }

    public function getOptionalParam(): string
    {
        return $this->optionalParam;
    }
}

