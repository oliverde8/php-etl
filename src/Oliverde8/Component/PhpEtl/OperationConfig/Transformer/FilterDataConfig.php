<?php

namespace Oliverde8\Component\PhpEtl\OperationConfig\Transformer;

use Oliverde8\Component\PhpEtl\Expression\Expression;
use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;
use Oliverde8\Component\RuleEngine\RuleConfig\RuleConfigInterface;

class FilterDataConfig extends AbstractOperationConfig
{
    /**
     * @param RuleConfigInterface|Expression|array $rules A typed RuleConfigInterface, an Expression (Symfony
     *                                                      Expression Language), or the legacy array-based Rule
     *                                                      Engine syntax (deprecated, triggers a deprecation notice
     *                                                      at runtime). Evaluated against the item's data; the
     *                                                      item is kept when the result is truthy (or falsy, if
     *                                                      $negate is true).
     * @param bool $negate Inverts the condition result.
     */
    public function __construct(
        public readonly RuleConfigInterface|Expression|array $rules = [],
        public readonly bool $negate = false,
        string $flavor = 'default',
    )
    {
        parent::__construct($flavor);
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
            'Passing an array of rules to FilterDataConfig is deprecated, pass a RuleConfigInterface or an Expression instead (e.g. new GetRuleConfig(...) or new Expression(\'...\')). See Oliverde8\Component\RuleEngine\RuleConfig and Oliverde8\Component\PhpEtl\Expression\Expression.',
        );
    }
}
