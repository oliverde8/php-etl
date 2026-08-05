<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Expression;

/**
 * A Symfony Expression Language condition, evaluated against `data` (the item's data) and `context` (the
 * execution context's parameters).
 */
final readonly class Expression
{
    public function __construct(public string $expression)
    {
    }
}
