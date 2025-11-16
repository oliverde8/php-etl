<?php

namespace Oliverde8\Component\PhpEtl\OperationConfig\Extract;

use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;

class JsonExtractConfig extends AbstractOperationConfig
{
    public function __construct(public readonly ?string $fileKey = null, string $flavor = 'default')
    {
        parent::__construct($flavor);
    }

    protected function validate(bool $constructOnly): void
    {}
}
