<?php

namespace Oliverde8\Component\PhpEtl\OperationConfig\Grouping;

use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;

class SimpleGroupingConfig extends AbstractOperationConfig
{
    public function __construct(
        public readonly array $groupKey,
        public readonly array $groupIdentifierKey = [],
        string $flavor = 'default'
    ) {
        parent::__construct($flavor);
    }


    #[\Override]
    protected function validate(bool $constructOnly): void
    {
        if (empty($this->groupKey)) {
            throw new \InvalidArgumentException('Group key cannot be empty');
        }
        foreach ($this->groupKey as $groupKey) {
            if (!is_string($groupKey)) {
                throw new \InvalidArgumentException('Group key must be an array of strings');
            }
        }
        foreach ($this->groupIdentifierKey as $groupIdentifierKey) {
            if (!is_string($groupIdentifierKey)) {
                throw new \InvalidArgumentException('Group identifier key must be an array of strings');
            }
        }
    }
}