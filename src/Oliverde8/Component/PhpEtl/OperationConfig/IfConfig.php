<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\OperationConfig;

use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Expression\Expression;
use Oliverde8\Component\RuleEngine\RuleConfig\RuleConfigInterface;

class IfConfig extends AbstractOperationConfig
{
    /**
     * @param ChainConfig $then Sub-chain executed when the condition is met.
     * @param RuleConfigInterface|Expression|array $rules A typed RuleConfigInterface, an Expression (Symfony
     *                                                      Expression Language), or the legacy array-based Rule
     *                                                      Engine syntax (deprecated, triggers a deprecation notice
     *                                                      at runtime). Evaluated against the item's data; the
     *                                                      item is routed to $then when the result is truthy (or
     *                                                      falsy, if $negate is true), otherwise to $else.
     * @param ChainConfig|null $else Optional sub-chain executed when the condition is not met. When omitted, the
     *                                item continues to the next step in the main chain unchanged.
     * @param bool $isolateContext When true, whichever branch runs does so against its own clone of the execution
     *                              context instead of sharing the parent's.
     */
    public function __construct(
        private readonly ChainConfig $then,
        public readonly RuleConfigInterface|Expression|array $rules = [],
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
        if (!is_array($this->rules)) {
            return;
        }

        if (empty($this->rules)) {
            throw new \InvalidArgumentException('rules must be provided');
        }

        trigger_deprecation(
            'oliverde8/php-etl',
            '2.1',
            'Passing an array of rules to IfConfig is deprecated, pass a RuleConfigInterface or an Expression instead (e.g. new GetRuleConfig(...) or new Expression(\'...\')). See Oliverde8\Component\RuleEngine\RuleConfig and Oliverde8\Component\PhpEtl\Expression\Expression.',
        );
    }
}
