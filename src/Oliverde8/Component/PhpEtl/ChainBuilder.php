<?php

declare(strict_types=1);

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
    protected array $operationFactories;

    protected ExecutionContextFactoryInterface $contextFactory;

    /**
     * @param ExecutionContextFactoryInterface $contextFactory
     */
    public function __construct(ExecutionContextFactoryInterface $contextFactory)
    {
        $this->contextFactory = $contextFactory;
    }


    /**
     * Register an operation factory.
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
     * @throws Exception\ChainBuilderValidationException
     * @throws UnknownOperationException
     */
    public function buildChainProcessor(array $configs): ChainProcessorInterface
    {
        $chainOperations = [];
        foreach ($configs as $id => $operation) {
            $chainOperations[$id] = $this->getOperationFromConfig($operation);
        }

        return new ChainProcessor($chainOperations, $this->contextFactory);
    }

    /**
     * Get chain operation instance from config.
     *
     * @throws Exception\ChainBuilderValidationException
     * @throws UnknownOperationException
     */
    protected function getOperationFromConfig(array $config): ChainOperationInterface
    {
        foreach ($this->operationFactories as $factory) {
            if ($factory->supports($config['operation'], $config['options'])) {
                return $factory->getOperation($config['operation'], $config['options']);
            }
        }

       throw new UnknownOperationException("No compatible factories were found for operation '{$config['operation']}'");
    }
}
