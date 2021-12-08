<?php

namespace Oliverde8\Component\PhpEtl;

use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem;

interface ExecutionContextFactoryInterface
{
    public function get(array $parameters): ExecutionContext;
}