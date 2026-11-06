<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainOperation\ChainSplitOperationV1;
use Oliverde8\Component\PhpEtl\ChainProcessorInterface;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;
use PHPUnit\Framework\TestCase;

class ChainSplitOperationV1Test extends TestCase
{
    public function testSplitSharesContextForDataAndStopItems(): void
    {
        $context = new ExecutionContext([], $this->createMock(FileSystemInterface::class));
        $processor = $this->createMock(ChainProcessorInterface::class);
        $processor->expects($this->exactly(2))
            ->method('processGenerator')
            ->with($this->anything(), $this->identicalTo($context))
            ->willReturnCallback(static function (ItemInterface $item, ExecutionContext $context): \Generator {
                $context->setParameter('calls', $context->getParameter('calls', 0) + 1);
                yield $item;
            });

        $operation = new ChainSplitOperationV1([$processor]);
        $input = new DataItem([]);
        $stop = new StopItem();

        self::assertSame($input, $operation->processData($input, $context));
        self::assertSame($stop, $operation->processStop($stop, $context));
        self::assertSame(2, $context->getParameter('calls'));
    }
}
