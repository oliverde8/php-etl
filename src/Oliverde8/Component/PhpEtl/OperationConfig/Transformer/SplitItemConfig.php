<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\OperationConfig\Transformer;

use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;

class SplitItemConfig extends AbstractOperationConfig
{
    public function __construct(
        public readonly array $keys,
        public readonly bool $singleElement = true,
        public readonly bool $keepKeys = false,
        public readonly ?string $keyName = null,
        public readonly array $duplicateKeys = [],
        string $flavor = 'default',
    ) {
        parent::__construct($flavor);
    }

    #[\Override]
    protected function validate(bool $constructOnly): void
    {
        if (empty($this->keys)) {
            throw new \InvalidArgumentException("Keys cannot be empty");
        }
    }
}

