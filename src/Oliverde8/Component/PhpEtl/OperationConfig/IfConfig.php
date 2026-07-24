<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\OperationConfig;

use Oliverde8\Component\PhpEtl\ChainConfig;

class IfConfig extends AbstractOperationConfig
{
    /**
     * @param array $rules Rule Engine rules, evaluated against the item's data. The item is routed to $then when
     *                      the result is truthy (or falsy, if $negate is true), otherwise to $else.
     * @param ChainConfig $then Sub-chain executed when the condition is met.
     * @param ChainConfig|null $else Optional sub-chain executed when the condition is not met. When omitted, the
     *                                item continues to the next step in the main chain unchanged.
     * @param bool $isolateContext When true, whichever branch runs does so against its own clone of the execution
     *                              context instead of sharing the parent's.
     */
    public function __construct(
        public readonly array $rules,
        private readonly ChainConfig $then,
        private readonly ?ChainConfig $else = null,
        public readonly bool $negate = false,
        public readonly bool $isolateContext = false,
        string $flavor = 'default',
    ) {
        parent::__construct($flavor);
    }

    public function getThenChainConfig(): ChainConfig
    {
        return $this->then;
    }

    public function getElseChainConfig(): ?ChainConfig
    {
        return $this->else;
    }

    #[\Override]
    protected function validate(bool $constructOnly): void
    {
        if (empty($this->rules)) {
            throw new \InvalidArgumentException('rules cannot be empty');
        }
    }
}
