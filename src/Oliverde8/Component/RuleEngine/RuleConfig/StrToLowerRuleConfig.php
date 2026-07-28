<?php

declare(strict_types=1);

namespace Oliverde8\Component\RuleEngine\RuleConfig;

final readonly class StrToLowerRuleConfig implements RuleConfigInterface
{
    public function __construct(public RuleConfigInterface $value)
    {
    }
}
