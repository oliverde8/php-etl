<?php

namespace Oliverde8\Component\PhpEtl\Builder\Factories\Transformer;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\RuleEngine\RuleApplier;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class RuleTransformFactory
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\Builder\Factories\Transformer
 */
class RuleTransformFactory extends AbstractFactory
{
    /** @var RuleApplier */
    protected $ruleApplier;

    /**
     * RuleTransformFactory constructor.
     *
     * @param string $operation
     * @param string $class
     * @param RuleApplier $ruleApplier
     */
    public function __construct($operation, $class, RuleApplier $ruleApplier)
    {
        parent::__construct($operation, $class);
        $this->ruleApplier = $ruleApplier;
    }

    /**
     * @inheritdoc
     */
    public function build($operation, $options)
    {
        return $this->create($this->ruleApplier, $options['columns'], $options['add']);
    }

    /**
     * @inheritdoc
     */
    protected function configureValidator()
    {
        return new Assert\Collection([
            'columns' => [
                new Assert\Type(["type" => "array"]),
                new Assert\NotBlank(),
            ],
            'add' => new Assert\Type(["type" => "boolean"])
        ]);
    }
}