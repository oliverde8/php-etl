<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\OperationConfig\Grouping;

use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;

class BatchConfig extends AbstractOperationConfig
{
    public function __construct(
        public readonly int $size,
        string $flavor = 'default',
    ) {
        parent::__construct($flavor);
    }

    #[\Override]
    protected function validate(bool $constructOnly): void
    {
        if ($this->size < 1) {
            throw new \InvalidArgumentException('size must be >= 1');
        }
    }
}
