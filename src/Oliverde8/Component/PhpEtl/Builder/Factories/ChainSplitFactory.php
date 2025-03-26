<?php

namespace Oliverde8\Component\PhpEtl\Builder\Factories;

use Oliverde8\Component\PhpEtl\ChainBuilder;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

class ChainSplitFactory extends AbstractFactory
{
    /** @var ChainBuilder */
    protected $builder;

    public function __construct(string $operation, string $class, ChainBuilder $builder)
    {
        parent::__construct($operation, $class);
        $this->builder = $builder;
    }

    /**
     * @inheritdoc
     */
    public function build(string $operation, array $options): ChainOperationInterface
    {
        $chainProcessors = [];
        foreach ($options['branches'] as $branch) {
            $chainProcessors[] = $this->builder->buildChainProcessor($branch, maxAsynchronousItems: $options['maxAsynchronousItems'] ?? 10);
        }

        return $this->create($chainProcessors);
    }

    protected function configureValidator(): Constraint
    {
        return new Assert\Collection([
            'branches' => [
                new Assert\Type(["type" => "array"]),
                new Assert\NotBlank(),
            ],
            'maxAsynchronousItems' => [
                new Assert\Type(["type" => "integer"]),
            ],
        ],
        allowMissingFields: true
        );
    }
}
