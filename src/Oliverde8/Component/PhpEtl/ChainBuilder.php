<?php

namespace Oliverde8\Component\PhpEtl;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;

/**
 * Class ChainBuilder
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl
 */
class ChainBuilder
{
    /** @var AbstractFactory[] */
    protected $operationFactories;

    /**
     * Register a operation factory.
     *
     * @param AbstractFactory $factory
     */
    public function registerFactory(AbstractFactory $factory)
    {
        $this->operationFactories[] = $factory;
    }

    /**
     * @param $configs
     *
     * @return ChainProcessorInterface
     */
    public function buildChainProcessor($configs)
    {
        $chainOperations = [];
        foreach ($configs as $id => $operation) {
            $chainOperations[$id] = $this->getOperationFromConfig($operation);
        }

        return new ChainProcessor($chainOperations);
    }

    /**
     * @param $config
     *
     * @return null|ChainOperation\ChainOperationInterface
     */
    protected function getOperationFromConfig($config)
    {
        foreach ($this->operationFactories as $factory) {
            if ($factory->supports($config['operation'], $config['options'])) {
                return $factory->getOperation($config['operation'], $config['options']);
            }
        }

        // TODO handle as error.
        return null;
    }
}
