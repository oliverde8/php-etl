<?php

namespace Oliverde8\Component\PhpEtl;

use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem;

class ExecutionContextFactory implements ExecutionContextFactoryInterface
{
    #[\Override]
    public function get(array $parameters): ExecutionContext
    {
        return new ExecutionContext($parameters, new LocalFileSystem());
    }
}
