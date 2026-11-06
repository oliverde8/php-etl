<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Tests\Expression;

use Oliverde8\Component\PhpEtl\Expression\ExpressionContext;
use Oliverde8\Component\PhpEtl\Expression\ExpressionEvaluator;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;
use PHPUnit\Framework\TestCase;

class ExpressionContextTest extends TestCase
{
    /** @var string[] */
    private array $deprecations = [];

    #[\Override]
    protected function setUp(): void
    {
        $this->deprecations = [];
        set_error_handler(function (int $errno, string $message): bool {
            $this->deprecations[] = $message;
            return true;
        }, E_USER_DEPRECATED);
    }

    #[\Override]
    protected function tearDown(): void
    {
        restore_error_handler();
    }

    public function testArraySyntaxReadsParametersWithoutDeprecation(): void
    {
        $context = $this->createContext(['limit' => 2, 'nested' => ['a' => 'b']]);
        $evaluator = new ExpressionEvaluator();

        $this->assertSame(2, $evaluator->evaluate('context["limit"]', ['context' => $context]));
        $this->assertSame('b', $evaluator->evaluate('context["nested"]["a"]', ['context' => $context]));
        $this->assertTrue(isset($context['limit']));
        $this->assertFalse(isset($context['missing']));
        $this->assertNull($context['missing']);
        $this->assertCount(2, $context);
        $this->assertSame(['limit' => 2, 'nested' => ['a' => 'b']], iterator_to_array($context));
        $this->assertSame([], $this->deprecations);
    }

    public function testDeprecatedMethodSyntaxStillWorks(): void
    {
        $context = $this->createContext(['limit' => 2, 'nested' => ['a' => 'b']]);
        $evaluator = new ExpressionEvaluator();

        $this->assertSame(2, $evaluator->evaluate('context.getParameter("limit")', ['context' => $context]));
        $this->assertSame('b', $evaluator->evaluate('context.getParameter("nested.a")', ['context' => $context]));
        $this->assertSame('d', $evaluator->evaluate('context.getParameter("missing", "d")', ['context' => $context]));
        $this->assertSame(2, $evaluator->evaluate('context.getParameters()["limit"]', ['context' => $context]));

        $this->assertCount(4, $this->deprecations);
        $this->assertStringContainsString('context.getParameter()', $this->deprecations[0]);
        $this->assertStringContainsString('context.getParameters()', $this->deprecations[3]);
    }

    public function testDeprecatedSetParameterStillWritesToTheExecutionContext(): void
    {
        $executionContext = new ExecutionContext(['counter' => 1], $this->createMock(FileSystemInterface::class));
        $context = new ExpressionContext($executionContext);

        (new ExpressionEvaluator())->evaluate('context.setParameter("counter", 5)', ['context' => $context]);

        $this->assertSame(5, $executionContext->getParameter('counter'));
        $this->assertCount(1, $this->deprecations);
    }

    public function testObjectParametersKeepTheirMethods(): void
    {
        $counter = new class {
            public int $value = 0;
            public function increment(): int
            {
                return ++$this->value;
            }
        };
        $context = $this->createContext(['counter' => $counter]);
        $evaluator = new ExpressionEvaluator();

        $this->assertSame(1, $evaluator->evaluate('context["counter"].increment()', ['context' => $context]));
        $this->assertSame(2, $evaluator->evaluate('context.getParameter("counter").increment()', ['context' => $context]));
    }

    public function testWritingThroughArraySyntaxIsRejected(): void
    {
        $this->expectException(\LogicException::class);

        $context = $this->createContext([]);
        $context['foo'] = 'bar';
    }

    private function createContext(array $parameters): ExpressionContext
    {
        return new ExpressionContext(new ExecutionContext($parameters, $this->createMock(FileSystemInterface::class)));
    }
}
