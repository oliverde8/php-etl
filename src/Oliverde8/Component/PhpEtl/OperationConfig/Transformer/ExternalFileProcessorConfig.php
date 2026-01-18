<?php

namespace Oliverde8\Component\PhpEtl\OperationConfig\Transformer;

use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;

class ExternalFileProcessorConfig extends AbstractOperationConfig
{
    public function __construct(
        string $flavor = 'default'
    ) {
        parent::__construct($flavor);
    }

    #[\Override]
    protected function validate(bool $constructOnly): void
    {
        // No validation needed as there are no configuration parameters
    }
}

