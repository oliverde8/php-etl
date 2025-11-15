<?php

namespace Oliverde8\Component\PhpEtl\OperationConfig\Transformer;

use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;

class RuleTransformConfig extends AbstractOperationConfig
{
    public function __construct(public readonly array $rules, public readonly bool $add, string $flavor)
    {
        parent::__construct($flavor);
    }

    protected function validate(): void
    {
    }
}
