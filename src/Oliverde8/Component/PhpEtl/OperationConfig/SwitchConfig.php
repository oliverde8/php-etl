<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\OperationConfig;

use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Exception\ChainBuilderException;
use Oliverde8\Component\PhpEtl\Expression\Expression;
use Oliverde8\Component\RuleEngine\RuleConfig\RuleConfigInterface;

/**
 * Routes an item to exactly one of several branches: cases are evaluated in the order they were added, the
 * first one whose condition matches wins, and its branch is free to transform the item since no other branch
 * runs alongside it. Falls back to $default (if provided) when no case matches, otherwise the item continues
 * unchanged to the next step in the main chain. N-way generalization of IfConfig's then/else.
 */
class SwitchConfig extends AbstractOperationConfig
{
    /** @var array<int, array{rules: RuleConfigInterface|Expression|array, then: ChainConfig}> */
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
     * @param RuleConfigInterface|Expression|array $rules A typed RuleConfigInterface, an Expression (Symfony
     *                                                      Expression Language), or the legacy array-based Rule
     *                                                      Engine syntax, evaluated against the item's data
     *                                                      (deprecated, triggers a deprecation notice at runtime).
     */
    public function addCase(ChainConfig $then, RuleConfigInterface|Expression|array $rules = []): self
    {
        if (is_array($rules)) {
            if (empty($rules)) {
                throw new \InvalidArgumentException('rules must be provided for a switch case');
            }

            trigger_deprecation(
                'oliverde8/php-etl',
                '2.1',
                'Passing an array of rules to SwitchConfig::addCase() is deprecated, pass a RuleConfigInterface or an Expression instead (e.g. new GetRuleConfig(...) or new Expression(\'...\')). See Oliverde8\Component\RuleEngine\RuleConfig and Oliverde8\Component\PhpEtl\Expression\Expression.',
            );
        }

        $this->cases[] = ['rules' => $rules, 'then' => $then];
        return $this;
    }

    /**
     * @return array<int, array{rules: RuleConfigInterface|Expression|array, then: ChainConfig}>
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
