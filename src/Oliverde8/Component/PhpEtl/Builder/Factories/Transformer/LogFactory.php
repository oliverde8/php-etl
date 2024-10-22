<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Builder\Factories\Transformer;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\Load\File\Json;
use Oliverde8\Component\RuleEngine\RuleApplier;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

class LogFactory  extends AbstractFactory
{
    public function __construct(string $operation, string $class)
    {
        parent::__construct($operation, $class);
    }

    protected function build($operation, $options): ChainOperationInterface
    {
        return $this->create($options['message'], $options['level'], $options['context'] ?? []);
    }

    protected function configureValidator(): Constraint
    {
        return new Assert\Collection([
            'message' => [new Assert\NotBlank(), new Assert\Type('string')],
            'level' => [new Assert\Choice(choices: ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'])],
            'context' => [
                new Assert\Type('array'),
                new Assert\All([
                    new Assert\Type('string')
                ])
            ],
        ]);


    }
}
