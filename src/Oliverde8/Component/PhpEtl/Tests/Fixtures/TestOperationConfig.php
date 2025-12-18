<?php

namespace Oliverde8\Component\PhpEtl\Tests\Fixtures;

use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;

class TestOperationConfig extends AbstractOperationConfig
{
    public function __construct(string $flavor = 'default')
    {
        parent::__construct($flavor);
    }

    #[\Override]
    protected function validate(bool $constructOnly): void
    {
    }
}

