<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Factory;

use Oliverde8\Component\PhpEtl\Model\ExecutionInterface;
use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;

interface FileSystemFactoryInterface
{
    public function get(ExecutionInterface $execution): FileSystemInterface;
}