<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Model;

interface ExecutionInterface
{
    public function getId(): string;

    public function GetCreateTime(): \DateTime;
}