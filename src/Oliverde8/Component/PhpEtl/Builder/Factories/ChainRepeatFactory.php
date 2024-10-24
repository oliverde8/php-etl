<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Builder\Factories;

use Oliverde8\Component\PhpEtl\ChainBuilder;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

class ChainRepeatFactory extends AbstractFactory
{
    public function __construct(string $operation, string $class, protected readonly ChainBuilder $builder)
    {
        parent::__construct($operation, $class);
    }

    public function build(string $operation, array $options): ChainOperationInterface
    {
        // Do not allow 
        $chainProcessor = $this->builder->buildChainProcessor($options['chain'],[], 0);
        return $this->create($chainProcessor,  $options['validationExpr']);
    }

    protected function configureValidator(): Constraint
    {
        return new Assert\Collection([
            'chain' => [
                new Assert\Type(["type" => "array"]),
                new Assert\NotBlank(),
            ],
            'validationExpr' => [
                new Assert\Type(["type" => "string"]),
                new Assert\NotBlank(),
            ],
        ]);
    }
}
