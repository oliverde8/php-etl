<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Builder\Factories\Loader;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\Load\File\Json;
use Oliverde8\Component\PhpEtl\Load\File\Xml;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

class XmlFileWriterFactory extends AbstractFactory
{
    protected function configureValidator(): Constraint
    {
        return new Assert\Collection([
            'file' => new Assert\NotBlank(),
        ]);
    }

    protected function build($operation, $options): ChainOperationInterface
    {
        $tmp = tempnam(sys_get_temp_dir(), 'etl');
        return $this->create(new Xml($tmp), $options['file']);
    }
}
