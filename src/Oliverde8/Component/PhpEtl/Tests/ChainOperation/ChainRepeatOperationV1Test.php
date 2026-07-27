<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainOperation\ChainRepeatOperationV1;
use Oliverde8\Component\PhpEtl\ChainProcessorInterface;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;
use PHPUnit\Framework\TestCase;

class ChainRepeatOperationV1Test extends TestCase
{
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
