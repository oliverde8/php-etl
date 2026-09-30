<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Expression;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final readonly class ExpressionEvaluator implements ExpressionEvaluatorInterface
{
    public function __construct(private ExpressionLanguage $expressionLanguage = new ExpressionLanguage(new ArrayAdapter(0, false)))
    {
    }

    #[\Override]
    public function evaluate(string $expression, array $variables): mixed
    {
        return $this->expressionLanguage->evaluate($expression, $variables);
    }

    #[\Override]
    public function evaluateIfExpression(string $value, array $variables): mixed
    {
        if (!str_starts_with($value, '@')) {
            return $value;
        }

        return $this->evaluate(ltrim($value, '@'), $variables);
    }
}
