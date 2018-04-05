<?php

namespace Oliverde8\Component\PhpEtl\Builder\Factories\Loader;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\Load\File\Csv;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class FileWriterFactory
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\Builder\Factories\Loader
 */
class CsvFileWriterFactory extends AbstractFactory
{
    /**
     * @inheritdoc
     */
    protected function configureValidator()
    {
        return new Assert\Collection([
            'file' => new Assert\NotBlank(),
        ]);
    }


    /**
     * @inheritdoc
     */
    protected function build($operation, $options)
    {
        return $this->create(new Csv($options['file']));
    }
}
