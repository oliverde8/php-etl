<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\Exception\UnknownOperationException;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

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

    protected ExpressionLanguage $expressionLanguage;

    /**
     * @param ExecutionContextFactoryInterface $contextFactory
     */
    public function __construct(ExecutionContextFactoryInterface $contextFactory)
    {
        $this->contextFactory = $contextFactory;

        $this->expressionLanguage = new ExpressionLanguage();
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
    public function buildChainProcessor(array $configs, array $inputOptions = []): ChainProcessorInterface
    {
        $chainOperations = [];
        foreach ($configs as $id => $operation) {
            $chainOperations[$id] = $this->getOperationFromConfig($operation, $inputOptions);
        }

        return new ChainProcessor($chainOperations, $this->contextFactory);
    }

    /**
     * Get chain operation instance from config.
     *
     * @throws Exception\ChainBuilderValidationException
     * @throws UnknownOperationException
     */
    protected function getOperationFromConfig(array $config, array $inputOptions): ChainOperationInterface
    {
        foreach ($config['options'] as &$option) {
            if (is_string($option) && strpos($option, "!") === 0) {
                $option = ltrim($option, '!');
                $option = $this->expressionLanguage->evaluate($option, $inputOptions);
            }
        }

        foreach ($this->operationFactories as $factory) {
            if ($factory->supports($config['operation'], $config['options'])) {
                return $factory->getOperation($config['operation'], $config['options']);
            }
        }

       throw new UnknownOperationException("No compatible factories were found for operation '{$config['operation']}'");
    }
}
