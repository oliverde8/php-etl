<?php

namespace Oliverde8\Component\PhpEtl\Builder\Factories\Grouping;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
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
    public function build($operation, $options)
    {
        return $this->create($options['grouping-key'], $options['group-identifier']);
    }

    /**
     * @inheritdoc
     */
    protected function configureValidator()
    {
        return new Assert\Collection([
             'grouping-key' => new Assert\NotBlank(),
             'group-identifier' => new Assert\Optional()
         ]);
    }

}