<?php

namespace Oliverde8\Component\PhpEtl\Builder\Factories;

use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;

/**
 * Class AbstractFactory
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\Builder\Factories
 */
abstract class AbstractFactory
{
    /** @var string The operation type */
    protected $operation;

    /** @var string Class to built */
    protected $class;

    /**
     * AbstractFactory constructor.
     *
     * @param string $operation
     * @param string $class
     */
    public function __construct($operation, $class)
    {
        $this->operation = $operation;
        $this->class = $class;
    }

    /**
     * Validate and Build an operation of a certain type with the options.
     *
     * @param String $operation
     * @param array $options
     *
     * @return ChainOperationInterface
     */
    public function getOperation($operation, $options)
    {
        $this->validateOptions($this, $options);
        return $this->build($operation, $options);
    }

    /**
     * Build an operation of a certain type with the options.
     *
     * @param String $operation
     * @param array $options
     *
     * @return ChainOperationInterface
     */
    abstract protected function build($type, $options);

    /**
     * Create the operation object.
     *
     * @param array ...$arguments
     *
     * @return ChainOperationInterface
     */
    protected function create(...$arguments)
    {
        $class = $this->class;
        return new $class(...$arguments);
    }

    /**
     * Validate the options.
     *
     * @param $operation
     * @param $options
     */
    protected function validateOptions($operation, $options)
    {
        // Does nothing for not. TODO validation.
    }

    /**
     * Check if the factory supports this operation declaration.
     *
     * @param $operation
     * @param $options
     *
     * @return bool
     */
    public function supports($operation, $options)
    {
        return $this->operation == $operation;
    }
}