<?php

namespace Oliverde8\Component\PhpEtl\OperationConfig\Transformer;

use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;

class FilterDataConfig extends AbstractOperationConfig
{
    /**
     * @param array $rules Rule Engine rules, evaluated against the item's data. Mutually exclusive with $expression.
     * @param bool $negate Inverts the condition result.
     * @param string|null $expression A Symfony Expression Language condition, evaluated against `data` (the item's
     *                                 data) and `context` (the execution context's parameters). Alternative to
     *                                 $rules for simple boolean conditions. Mutually exclusive with $rules.
     */
    public function __construct(
        public readonly array $rules = [],
        public readonly bool $negate = false,
        public readonly ?string $expression = null,
        string $flavor = 'default',
    )
    {
        parent::__construct($flavor);
    }

    #[\Override]
    protected function validate(bool $constructOnly): void
    {
        if (empty($this->rules) && $this->expression === null) {
            throw new \InvalidArgumentException('Either rules or expression must be provided');
        }
        if (!empty($this->rules) && $this->expression !== null) {
            throw new \InvalidArgumentException('rules and expression are mutually exclusive');
        }
    }
}