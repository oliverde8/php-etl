<?php

namespace Oliverde8\Component\PhpEtl\Tests\Fixtures;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\OperationConfigInterface;

class TestOperationWithConfigAndInjection extends AbstractChainOperation implements ConfigurableChainOperationInterface
{
    public function __construct(
        private readonly \stdClass $service,
        private readonly OperationConfigInterface $config
    ) {
    }

    public function getService(): \stdClass
    {
        return $this->service;
    }

    public function getConfig(): OperationConfigInterface
    {
        return $this->config;
    }
}

