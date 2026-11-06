<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Expression;

use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;

/**
 * Exposes an ExecutionContext to an expression as `context`.
 *
 * Supports the array syntax (`context["key"]`) used by every expression-aware operation, and
 * keeps the method syntax (`context.getParameter("key")`) that ChainRepeatOperation's
 * validationExpression accepted before 2.1, as a deprecated alias.
 *
 * @implements \ArrayAccess<string, mixed>
 * @implements \IteratorAggregate<string, mixed>
 */
final class ExpressionContext implements \ArrayAccess, \IteratorAggregate, \Countable
{
    public function __construct(private readonly ExecutionContext $context)
    {
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->context->getParameters());
    }

    #[\Override]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->context->getParameters()[$offset] ?? null;
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('The expression context is read-only.');
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('The expression context is read-only.');
    }

    #[\Override]
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->context->getParameters());
    }

    #[\Override]
    public function count(): int
    {
        return count($this->context->getParameters());
    }

    /**
     * @deprecated since 2.1.1, use `context` as an array in expressions instead.
     */
    public function getParameters(): array
    {
        $this->deprecate('getParameters()', 'context');

        return $this->context->getParameters();
    }

    /**
     * @deprecated since 2.1.1, use `context["key"]` in expressions instead.
     */
    public function getParameter(string $key, $default = null, string $separator = ".")
    {
        $this->deprecate('getParameter()', 'context["key"]');

        return $this->context->getParameter($key, $default, $separator);
    }

    /**
     * @deprecated since 2.1.1, expressions should not modify the execution context.
     */
    public function setParameter(string $key, $value, string $separator = "."): void
    {
        $this->deprecate('setParameter()', 'an operation such as a callback transformer');

        $this->context->setParameter($key, $value, $separator);
    }

    /**
     * @deprecated since 2.1.1, the file system is no longer exposed to expressions.
     */
    public function getFileSystem(): FileSystemInterface
    {
        $this->deprecate('getFileSystem()', 'an operation that receives the execution context');

        return $this->context->getFileSystem();
    }

    /**
     * @deprecated since 2.1.1, the logger context is no longer exposed to expressions.
     */
    public function getLoggerContext(array $additionalContextData): array
    {
        $this->deprecate('getLoggerContext()', 'an operation that receives the execution context');

        return $this->context->getLoggerContext($additionalContextData);
    }

    private function deprecate(string $method, string $replacement): void
    {
        trigger_deprecation(
            'oliverde8/php-etl',
            '2.1.1',
            'Calling "context.%s" in an expression is deprecated, use %s instead.',
            $method,
            $replacement
        );
    }
}
