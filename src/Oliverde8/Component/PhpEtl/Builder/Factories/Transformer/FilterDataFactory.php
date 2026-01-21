<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Builder\Factories\Transformer;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\RuleEngine\RuleApplier;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class RuleTransformFactory
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\Builder\Factories\Transformer
 */
class FilterDataFactory extends AbstractFactory
{
    protected RuleApplier $ruleApplier;

    /**
     * RuleTransformFactory constructor.
     *
     * @param string $operation
     * @param string $class
     * @param RuleApplier $ruleApplier
     */
    public function __construct(string $operation, string $class, RuleApplier $ruleApplier)
    {
        parent::__construct($operation, $class);
        $this->ruleApplier = $ruleApplier;
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function build(string $operation, array $options): ChainOperationInterface
    {
        return $this->create(
            $this->ruleApplier,
            new FilterDataConfig($options['rule'], $options['negate'])
        );
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    protected function configureValidator(): Constraint
    {
        return new Assert\Collection([
            'rule' => [
                new Assert\Type(["type" => "array"]),
                new Assert\NotBlank(),
            ],
            'negate' => new Assert\Type(["type" => "boolean"])
        ]);
    }
}
