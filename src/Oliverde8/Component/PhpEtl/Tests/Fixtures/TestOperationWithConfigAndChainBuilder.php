<?php

namespace Oliverde8\Component\PhpEtl\Tests\Fixtures;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\OperationConfigInterface;

class TestOperationWithConfigAndChainBuilder extends AbstractChainOperation implements ConfigurableChainOperationInterface
{
    public function __construct(
        private readonly ChainBuilderV2 $chainBuilder,
        private readonly OperationConfigInterface $config
    ) {
    }

    public function getChainBuilder(): ChainBuilderV2
    {
        return $this->chainBuilder;
    }

    public function getConfig(): OperationConfigInterface
    {
        return $this->config;
    }
}

