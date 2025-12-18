<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Builder\Factories\Transformer;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\Extract\ExternalFileFinderOperation;

class ExternalFileProcessorFactory extends AbstractFactory
{
    #[\Override]
    protected function build(string $operation, array $options): ChainOperationInterface
    {
        return $this->create();
    }
}
