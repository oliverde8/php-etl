<?php

namespace Oliverde8\Component\PhpEtl\OperationConfig\Extract;

use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;

class ExternalFileFinderConfig extends AbstractOperationConfig
{
    public function __construct(
        public readonly string $directory,
        string $flavor = 'default'
    ) {
        parent::__construct($flavor);
    }

    protected function validate(bool $constructOnly): void
    {
        if (empty($this->directory)) {
            throw new \InvalidArgumentException('Directory cannot be empty');
        }
        if (!is_string($this->directory)) {
            throw new \InvalidArgumentException('Directory must be a string');
        }
    }
}

