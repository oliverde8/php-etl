<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Builder\Factories\Grouping;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\Grouping\SimpleGroupingConfig;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class SimpleGroupingFactory
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\Builder\Factories\Grouping
 */
class SimpleGroupingFactory extends AbstractFactory
{
    /**
     * @inheritdoc
     */
    #[\Override]
    public function build(string $operation, array $options): ChainOperationInterface
    {
        return $this->create(
            new SimpleGroupingConfig(
                $options['grouping-key'],
                $options['group-identifier']
            )
        );
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    protected function configureValidator(): Constraint
    {
        return new Assert\Collection([
             'grouping-key' => new Assert\NotBlank(),
             'group-identifier' => new Assert\Optional()
         ]);
    }
}
