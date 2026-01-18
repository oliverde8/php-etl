<?php

namespace Oliverde8\Component\PhpEtl\Tests\Fixtures;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\OperationConfigInterface;

class TestOperationWithFlavor extends AbstractChainOperation implements ConfigurableChainOperationInterface
{
    public function __construct(
        private readonly OperationConfigInterface $config,
        private readonly string $flavor
    ) {
    }

    public function getFlavor(): string
    {
        return $this->flavor;
    }
}

