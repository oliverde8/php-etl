<?php

namespace Oliverde8\Component\PhpEtl\OperationConfig\Transformer;

use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;

class RuleTransformConfig extends AbstractOperationConfig
{
    protected array $rules = [];

    public function __construct(public readonly bool $add, string $flavor)
    {
        parent::__construct($flavor);
    }

    public function addColumn(string $columnName, array $rules): self
    {
        $this->rules[$columnName]['rules'] = $rules;
        return $this;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    protected function validate(): void
    {
    }
}
