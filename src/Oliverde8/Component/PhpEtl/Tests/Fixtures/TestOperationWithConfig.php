<?php

namespace Oliverde8\Component\PhpEtl\Tests\Fixtures;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\OperationConfigInterface;

class TestOperationWithConfig extends AbstractChainOperation implements ConfigurableChainOperationInterface
{
    public function __construct(private readonly OperationConfigInterface $config)
    {
    }

    public function getConfig(): OperationConfigInterface
    {
        return $this->config;
    }
}

