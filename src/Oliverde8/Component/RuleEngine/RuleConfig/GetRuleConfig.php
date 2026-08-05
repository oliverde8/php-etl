<?php

declare(strict_types=1);

namespace Oliverde8\Component\RuleEngine\RuleConfig;

final readonly class GetRuleConfig implements RuleConfigInterface
{
    /**
     * @param string|string[] $field The key (or list of keys, for a multi-value lookup) of the input array to
     *                                 retrieve the value from.
     */
    public function __construct(public string|array $field)
    {
    }
}
