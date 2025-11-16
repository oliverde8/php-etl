<?php

namespace Oliverde8\Component\PhpEtl\OperationConfig\Transformer;

use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;

class FilterDataConfig extends AbstractOperationConfig
{
    public function __construct(
        public readonly array $rules,
        public readonly bool $negate = false,
        string $flavor = 'default',
    )
    {
        parent::__construct($flavor);
    }

    protected function validate(bool $constructOnly): void
    {}
}