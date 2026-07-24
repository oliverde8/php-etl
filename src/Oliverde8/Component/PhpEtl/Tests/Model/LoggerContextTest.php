<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Tests\Model;

use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem;
use PHPUnit\Framework\TestCase;

class LoggerContextTest extends TestCase
{
    public function testSetLoggerContextPersistsTheValue(): void
    {
        $context = $this->getExecutionContext();
        $context->setLoggerContextPublic('etl.identifier', 'chain link:my-link-');

        $this->assertSame(
            ['etl' => ['identifier' => 'chain link:my-link-']],
            $context->getLoggerContext([])
        );
    }

    public function testSetLoggerContextIsMergedWithAdditionalContextData(): void
    {
        $context = $this->getExecutionContext();
        $context->setLoggerContextPublic('etl.identifier', 'chain link:my-link-');

        $this->assertSame(
            ['message' => 'hello', 'etl' => ['identifier' => 'chain link:my-link-']],
            $context->getLoggerContext(['message' => 'hello'])
        );
    }

    private function getExecutionContext(): ExecutionContext
    {
        return new class([], new LocalFileSystem()) extends ExecutionContext {
            public function setLoggerContextPublic($key, $value): void
            {
                $this->setLoggerContext($key, $value);
            }
        };
    }
}
