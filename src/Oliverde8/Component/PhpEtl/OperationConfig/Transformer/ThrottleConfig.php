<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\OperationConfig\Transformer;

use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;

class ThrottleConfig extends AbstractOperationConfig
{
    public function __construct(
        public readonly int $intervalMs,
        string $flavor = 'default',
    ) {
        parent::__construct($flavor);
    }

    #[\Override]
    protected function validate(bool $constructOnly): void
    {
        if ($this->intervalMs <= 0) {
            throw new \InvalidArgumentException("Interval must be greater than 0 ms. Got: {$this->intervalMs}");
        }
    }
}
