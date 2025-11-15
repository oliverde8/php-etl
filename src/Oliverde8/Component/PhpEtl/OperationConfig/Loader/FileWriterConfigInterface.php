<?php

namespace Oliverde8\Component\PhpEtl\OperationConfig\Loader;

use Oliverde8\Component\PhpEtl\Load\File\FileWriterInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\OperationConfigInterface;

interface FileWriterConfigInterface extends OperationConfigInterface
{
    public function getFileName(): string;

    public function getFile(): FileWriterInterface;
}