<?php

declare(strict_types=1);

namespace Oliverde8\Component\RuleEngine\RuleConfig;

final readonly class ImplodeRuleConfig implements RuleConfigInterface
{
    /**
     * @param RuleConfigInterface[] $values Rules resolved and imploded together, in order.
     * @param string $with The delimiter used to join the values.
     */
    public function __construct(
        public array $values,
        public string $with = '',
    ) {
    }
}
