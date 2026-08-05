<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Expression;

interface ExpressionEvaluatorInterface
{
    /**
     * Evaluate an expression against a set of variables.
     */
    public function evaluate(string $expression, array $variables): mixed;

    /**
     * Return $value as-is unless it is prefixed with "@", in which case the rest of the
     * string is evaluated as an expression against $variables.
     */
    public function evaluateIfExpression(string $value, array $variables): mixed;
}
