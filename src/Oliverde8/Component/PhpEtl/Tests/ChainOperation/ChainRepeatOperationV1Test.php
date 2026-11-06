<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainOperation\ChainRepeatOperationV1;
use Oliverde8\Component\PhpEtl\ChainProcessorInterface;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class ChainRepeatOperationV1Test extends TestCase
{
    public function testRepeatSharesContextAndStopsOnInvalidItem(): void
    {
        $input = new DataItem([]);
        $context = new ExecutionContext([], $this->createMock(FileSystemInterface::class));
        $processor = $this->createMock(ChainProcessorInterface::class);
        $processor->expects($this->exactly(3))
            ->method('processGenerator')
            ->with($input, $this->identicalTo($context), null, false, false)
            ->willReturnCallback(static function () use ($context): \Generator {
                $step = $context->getParameter('step', 0) + 1;
                $context->setParameter('step', $step);
                yield new DataItem(['step' => $step]);
            });

        $operation = new ChainRepeatOperationV1($processor, 'data["step"] < 3');
        $items = iterator_to_array($operation->repeatOnItem($input, $context));

        self::assertEquals([new DataItem(['step' => 1]), new DataItem(['step' => 2])], $items);
        self::assertSame(3, $context->getParameter('step'));
    }

    public function testItemIsValidEvaluatesExpressionWithoutCallingParentConstructor(): void
    {
        $operation = new ChainRepeatOperationV1(
            $this->createMock(ChainProcessorInterface::class),
            'data["val"] != 3',
        );

        $context = new ExecutionContext([], $this->createMock(FileSystemInterface::class));

        $this->assertTrue($operation->itemIsValid(new DataItem(['val' => 1]), $context));
        $this->assertFalse($operation->itemIsValid(new DataItem(['val' => 3]), $context));
    }
}
