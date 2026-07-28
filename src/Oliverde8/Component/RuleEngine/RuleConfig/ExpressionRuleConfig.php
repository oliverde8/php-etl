<?php

declare(strict_types=1);

namespace Oliverde8\Component\RuleEngine\RuleConfig;

final readonly class ExpressionRuleConfig implements RuleConfigInterface
{
    /**
     * @param string $expression Symfony Expression Language expression. `rowData` and `transformedData` are
     *                            always available; each key of $values is additionally available under that name.
     * @param array<string, RuleConfigInterface> $values Named additional variables made available to the expression.
     */
    public function __construct(
        public string $expression,
        public array $values = [],
    ) {
    }
}
