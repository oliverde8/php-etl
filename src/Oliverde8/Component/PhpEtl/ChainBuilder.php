<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainMergeOperation;
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

    /** @var ChainProcessor[] */
    protected array $subChainProcessors = [];

    protected ExpressionLanguage $expressionLanguage;

    /**
     * @param ExecutionContextFactoryInterface $contextFactory
     */
    public function __construct(protected ExecutionContextFactoryInterface $contextFactory)
    {
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
    public function buildChainProcessor(array $configs, array $inputOptions = [], int $maxAsynchronousItems = 10, ?array $subChainProcessors = null): ChainProcessorInterface
    {
        if ($subChainProcessors) {
            $this->subChainProcessors = $subChainProcessors;
        }

        $mainChainConfig = $configs;
        if (isset($configs['chain'])) {
            $mainChainConfig = $configs['chain'];
        }

        foreach ($configs['subChains'] ?? [] as $subChainName => $subChainConfigs) {
            $chainOperations = [];
            foreach ($subChainConfigs['chain'] as $id => $operation) {
                $chainOperations[$id] = $this->getOperationFromConfig($operation, $inputOptions);
            }
            $this->subChainProcessors[$subChainName] = new ChainProcessor(
                $chainOperations,
                $this->contextFactory,
                $maxAsynchronousItems,
                $subChainConfigs['shared'] ?? false
            );
        }

        $chainOperations = [];
        foreach ($mainChainConfig as $id => $operation) {
            $chainOperations[$id] = $this->getOperationFromConfig($operation, $inputOptions);
        }

        return new ChainProcessor($chainOperations, $this->contextFactory, $maxAsynchronousItems);
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
            if (is_string($option) && str_starts_with($option, "!")) {
                $option = ltrim($option, '!');
                $option = $this->expressionLanguage->evaluate($option, $inputOptions);
            }
        }

        if (strtolower((string) $config['operation']) == 'subchain') {
            $subChain = $config['options']['name'];
            if (!isset($this->subChainProcessors[$subChain])) {
                throw new UnknownOperationException("No subchain '$subChain' was found to create operation");
            }

            $chainOperation = $this->subChainProcessors[$subChain];
            // In case the operation is shared create a clone. If not use the same operation.
            if ($chainOperation instanceof ChainProcessor && !$chainOperation->isShared()) {
                $chainOperation = clone $chainOperation;
            }
            // Use the merge operation to execute a sub chain and not split. Merge with a single operation will return
            // the result of the sub chain to the next steps of the chain.
            return new ChainMergeOperation([$chainOperation]);
        } else {
            foreach ($this->operationFactories as $factory) {
                if ($factory->supports($config['operation'], $config['options'])) {
                    return $factory->getOperation($config['operation'], $config['options']);
                }
            }
        }

       throw new UnknownOperationException("No compatible factories were found for operation '{$config['operation']}'");
    }
}
