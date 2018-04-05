<?php

namespace Oliverde8\Component\PhpEtl\Builder\Factories\Loader;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\Load\File\Csv;
use Oliverde8\Component\PhpEtl\Load\File\FileWriterInterface;

/**
 * Class FileWriterFactory
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\Builder\Factories\Loader
 */
class FileWriterFactory extends AbstractFactory
{
    /**
     * Build an operation of a certain type with the options.
     *
     * @param String $operation
     * @param array $options
     *
     * @return ChainOperationInterface
     */
    protected function build($operation, $options)
    {
        // TODO handle other then csv.
        return $this->create(new Csv($options['file']));
    }
}
