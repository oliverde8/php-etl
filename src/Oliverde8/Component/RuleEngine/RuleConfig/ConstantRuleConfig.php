<?php

declare(strict_types=1);

namespace Oliverde8\Component\RuleEngine\RuleConfig;

final readonly class ConstantRuleConfig implements RuleConfigInterface
{
    public function __construct(public mixed $value)
    {
    }
}
