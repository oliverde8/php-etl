<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Tests\Expression;

use Oliverde8\Component\PhpEtl\Expression\ExpressionEvaluator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ExpressionEvaluatorTest extends TestCase
{
    public function testEvaluate(): void
    {
        $evaluator = new ExpressionEvaluator();

        $this->assertSame(3, $evaluator->evaluate('data["a"] + data["b"]', ['data' => ['a' => 1, 'b' => 2]]));
    }

    public function testEvaluateIfExpressionReturnsLiteralAsIs(): void
    {
        $evaluator = new ExpressionEvaluator();

        $this->assertSame('a plain string', $evaluator->evaluateIfExpression('a plain string', []));
    }

    public function testEvaluateIfExpressionEvaluatesAtPrefixedValue(): void
    {
        $evaluator = new ExpressionEvaluator();

        $this->assertSame('bar', $evaluator->evaluateIfExpression('@data["foo"]', ['data' => ['foo' => 'bar']]));
    }

    public function testCustomExpressionLanguageWithRegisteredFunctionCanBeInjected(): void
    {
        $expressionLanguage = new ExpressionLanguage();
        $expressionLanguage->addFunction(new ExpressionFunction(
            'shout',
            fn($str) => sprintf('strtoupper(%s)', $str),
            fn($arguments, $str) => strtoupper((string) $str),
        ));

        $evaluator = new ExpressionEvaluator($expressionLanguage);

        $this->assertSame('HELLO', $evaluator->evaluate('shout("hello")', []));
    }
}
