<?php

namespace Oliverde8\Component\PhpEtl;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\Exception\UnknownOperationException;

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
     * Get chain processor from configs.
     *
     * @param $configs
     *
     * @return ChainProcessor
     * @throws Exception\ChainBuilderValidationException
     * @throws UnknownOperationException
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
     * Get chain operation instance from config.
     *
     * @param $config
     *
     * @return ChainOperationInterface
     *
     * @throws Exception\ChainBuilderValidationException
     * @throws UnknownOperationException
     */
    protected function getOperationFromConfig($config)
    {
        foreach ($this->operationFactories as $factory) {
            if ($factory->supports($config['operation'], $config['options'])) {
                return $factory->getOperation($config['operation'], $config['options']);
            }
        }

       throw new UnknownOperationException("No compatible factories were found for operation '{$config['operation']}'");
    }
}
