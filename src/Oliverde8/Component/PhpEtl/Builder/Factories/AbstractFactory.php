<?php

namespace Oliverde8\Component\PhpEtl\Builder\Factories;

use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\Exception\ChainBuilderValidationException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;


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
     * @throws ChainBuilderValidationException
     */
    public function getOperation($operation, $options)
    {
        $this->validateOptions($operation, $options);
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
    abstract protected function build($operation, $options);

    /**
     * Configure validation.
     *
     * @return Constraint
     */
    protected function configureValidator()
    {
        return new Assert\Collection([]);
    }

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
     * @param $options
     * @throws ChainBuilderValidationException
     */
    protected function validateOptions($operation, $options)
    {
        $constraints = $this->configureValidator();
        $violations = Validation::createValidator()->validate($options, $constraints);

        if ($violations->count() != 0) {
            throw new ChainBuilderValidationException($operation, $violations);
        }

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