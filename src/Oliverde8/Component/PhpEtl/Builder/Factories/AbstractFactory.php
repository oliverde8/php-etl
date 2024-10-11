<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Builder\Factories;

use Composer\InstalledVersions;
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
    public function __construct(string $operation, string $class)
    {
        $this->operation = $operation;
        $this->class = $class;
    }

    /**
     * Validate and Build an operation of a certain type with the options.
     *
     * @param string $operation
     * @param array $options
     *
     * @return ChainOperationInterface
     * @throws ChainBuilderValidationException
     */
    public function getOperation(string $operation, array $options): ChainOperationInterface
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
    abstract protected function build(string $operation, array $options): ChainOperationInterface;

    /**
     * Configure validation.
     *
     * @return Constraint
     */
    protected function configureValidator(): Constraint
    {
        $symfonyValidatorVersion = InstalledVersions::getVersion('symfony/validator');
        if (version_compare('5.4', $symfonyValidatorVersion)) {
            return new Assert\Collection(fields: []);
        } else {
            // This code is here to continue to support all version of symfony because of Magento2 still using
            // 5.0 and even 4.0 version of some symfony packages. This lib being used by multiple Magento2 projects
            // I need to keep supporting it.
            return new Assert\Collection(['fields' => []]);
        }
    }

    /**
     * @param mixed ...$arguments
     * @return ChainOperationInterface
     */
    protected function create(...$arguments): ChainOperationInterface
    {
        $class = $this->class;
        return new $class(...$arguments);
    }

    /**
     * Validate the options.
     *
     * @param string $operation
     * @param array $options
     * @throws ChainBuilderValidationException
     */
    protected function validateOptions(string $operation, array $options): void
    {
        $constraints = $this->configureValidator();
        $violations = Validation::createValidator()->validate($options, $constraints);

        if ($violations->count() != 0) {
            throw new ChainBuilderValidationException($operation, iterator_to_array($violations));
        }
    }

    /**
     * Check if the factory supports this operation declaration.
     *
     * @param string $operation
     * @param array $options
     *
     * @return bool
     */
    public function supports(string $operation, array $options)
    {
        return $this->operation == $operation;
    }
}