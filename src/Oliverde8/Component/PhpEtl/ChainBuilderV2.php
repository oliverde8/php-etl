<?php

namespace Oliverde8\Component\PhpEtl;

use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\OperationConfigInterface;

class ChainBuilderV2
{

    /**
     * @param ExecutionContextFactoryInterface $contextFactory
     * @param iterable<GenericChainFactory> $factories
     */
    public function __construct(
        private readonly ExecutionContextFactoryInterface $contextFactory,
        private iterable $factories = [],
    ) {}

    public function createChain(ChainConfig $chainConfig): ChainProcessorInterface
    {
        $operations = [];
        foreach ($chainConfig->getConfigs() as $linkConfig) {
            $operations[] = $this->getOperationFromConfig($linkConfig);
        }
        return new ChainProcessor($operations, $this->contextFactory, $chainConfig->maxAsynchronousItems);
    }

    private function getOperationFromConfig(OperationConfigInterface $linkConfig): ConfigurableChainOperationInterface
    {
        foreach ($this->factories as $factory) {
            if ($factory->supports($linkConfig)) {
                return $factory->build($linkConfig, $this);
            }
        }

        throw new \RuntimeException('No factory found for link config of type ' . get_class($linkConfig));
    }
}