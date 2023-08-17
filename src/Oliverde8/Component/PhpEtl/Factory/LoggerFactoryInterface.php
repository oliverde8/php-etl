<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Factory;

use Oliverde8\Component\PhpEtl\Model\ExecutionInterface;
use Psr\Log\LoggerInterface;

interface LoggerFactoryInterface
{
    public function get(ExecutionInterface $execution): LoggerInterface;
}