<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Builder\Factories\Transformer;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\LogConfig;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

class LogFactory  extends AbstractFactory
{
    #[\Override]
    protected function build($operation, $options): ChainOperationInterface
    {
        return $this->create(
            new LogConfig(
                $options['message'],
                $options['level'],
                $options['context'] ?? []
            )
        );
    }

    #[\Override]
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
