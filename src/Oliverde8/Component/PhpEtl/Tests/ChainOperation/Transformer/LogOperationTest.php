<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation\Transformer;

use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\LogOperation;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;
use Oliverde8\Component\PhpEtl\Model\PerExecutionExecutionContext;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\LogConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LogOperationTest extends TestCase
{
    public function testLiteralMessage(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with('a plain message', []);

        $operation = new LogOperation(new LogConfig('a plain message'));
        $operation->process(new DataItem(['foo' => 'bar']), $this->createContext($logger));
    }

    public function testExpressionMessageUsesData(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with('Hello bar', []);

        $operation = new LogOperation(new LogConfig('@"Hello " ~ data["foo"]'));
        $operation->process(new DataItem(['foo' => 'bar']), $this->createContext($logger));
    }

    public function testExpressionMessageUsesContext(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with('run-42', []);

        $operation = new LogOperation(new LogConfig('@"run-" ~ context["runId"]'));
        $operation->process(new DataItem([]), $this->createContext($logger, ['runId' => 42]));
    }

    public function testLogLevelIsRespected(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with('boom', []);

        $operation = new LogOperation(new LogConfig('boom', 'error'));
        $operation->process(new DataItem([]), $this->createContext($logger));
    }

    public function testLogContextIsExtractedFromData(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with('a message', ['id' => 7]);

        $operation = new LogOperation(new LogConfig('a message', 'info', ['id' => 'itemId']));
        $operation->process(new DataItem(['itemId' => 7]), $this->createContext($logger));
    }

    private function createContext(LoggerInterface $logger, array $parameters = []): PerExecutionExecutionContext
    {
        return new PerExecutionExecutionContext($parameters, $this->createMock(FileSystemInterface::class), $logger, '/tmp');
    }
}
