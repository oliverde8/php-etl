<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\OperationConfig;

use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Exception\ChainBuilderException;

/**
 * Routes an item to exactly one of several branches: cases are evaluated in the order they were added, the
 * first one whose condition matches wins, and its branch is free to transform the item since no other branch
 * runs alongside it. Falls back to $default (if provided) when no case matches, otherwise the item continues
 * unchanged to the next step in the main chain. N-way generalization of IfConfig's then/else.
 */
class SwitchConfig extends AbstractOperationConfig
{
    /** @var array<int, array{rules: array, expression: ?string, then: ChainConfig}> */
    private array $cases = [];

    /**
     * @param ChainConfig|null $default Optional sub-chain executed when no case matches. When omitted, the item
     *                                   continues to the next step in the main chain unchanged.
     * @param bool $isolateContext When true, whichever branch runs does so against its own clone of the execution
     *                              context instead of sharing the parent's.
     */
    public function __construct(
        private readonly ?ChainConfig $default = null,
        public readonly bool $isolateContext = false,
        string $flavor = 'default',
    ) {
        parent::__construct($flavor);
    }

    /**
     * @param ChainConfig $then Sub-chain executed when this case matches.
     * @param array $rules Rule Engine rules, evaluated against the item's data. Mutually exclusive with $expression.
     * @param string|null $expression A Symfony Expression Language condition, evaluated against `data` (the item's
     *                                 data) and `context` (the execution context's parameters). Alternative to
     *                                 $rules for simple boolean conditions. Mutually exclusive with $rules.
     */
    public function addCase(ChainConfig $then, array $rules = [], ?string $expression = null): self
    {
        if (empty($rules) && $expression === null) {
            throw new \InvalidArgumentException('Either rules or expression must be provided for a switch case');
        }
        if (!empty($rules) && $expression !== null) {
            throw new \InvalidArgumentException('rules and expression are mutually exclusive for a switch case');
        }

        $this->cases[] = ['rules' => $rules, 'expression' => $expression, 'then' => $then];
        return $this;
    }

    /**
     * @return array<int, array{rules: array, expression: ?string, then: ChainConfig}>
     */
    public function getCases(): array
    {
        return $this->cases;
    }

    public function getDefaultChainConfig(): ?ChainConfig
    {
        return $this->default;
    }

    #[\Override]
    protected function validate(bool $constructOnly): void
    {
        if ($constructOnly) {
            return;
        }

        if (empty($this->cases)) {
            throw new ChainBuilderException("At least one case must be provided for SwitchConfig");
        }
    }
}
