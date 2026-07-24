<?php

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation\Grouping;

use Oliverde8\Component\PhpEtl\ChainOperation\Grouping\BatchOperation;
use Oliverde8\Component\PhpEtl\Item\ChainBreakItem;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem;
use Oliverde8\Component\PhpEtl\OperationConfig\Grouping\BatchConfig;
use PHPUnit\Framework\TestCase;

class BatchOperationTest extends TestCase
{
    private ExecutionContext $context;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->context = new ExecutionContext([], new LocalFileSystem());
    }

    public function testBufferedItemsDoNotEmitUntilBatchIsFull(): void
    {
        $operation = new BatchOperation(new BatchConfig(size: 3));

        $result1 = $operation->process(new DataItem(['id' => 1]), $this->context);
        $result2 = $operation->process(new DataItem(['id' => 2]), $this->context);

        $this->assertInstanceOf(ChainBreakItem::class, $result1);
        $this->assertInstanceOf(ChainBreakItem::class, $result2);
    }

    public function testFullBatchEmitsSingleDataItemWithAllRecords(): void
    {
        $operation = new BatchOperation(new BatchConfig(size: 3));

        $operation->process(new DataItem(['id' => 1]), $this->context);
        $operation->process(new DataItem(['id' => 2]), $this->context);
        $result = $operation->process(new DataItem(['id' => 3]), $this->context);

        $this->assertInstanceOf(DataItem::class, $result);
        $this->assertEquals([['id' => 1], ['id' => 2], ['id' => 3]], $result->getData());
    }

    public function testBufferResetsAfterEmittingABatch(): void
    {
        $operation = new BatchOperation(new BatchConfig(size: 2));

        $operation->process(new DataItem(['id' => 1]), $this->context);
        $operation->process(new DataItem(['id' => 2]), $this->context);
        $result3 = $operation->process(new DataItem(['id' => 3]), $this->context);

        $this->assertInstanceOf(ChainBreakItem::class, $result3);
    }

    public function testMultipleFullBatches(): void
    {
        $operation = new BatchOperation(new BatchConfig(size: 2));

        $operation->process(new DataItem(['id' => 1]), $this->context);
        $batch1 = $operation->process(new DataItem(['id' => 2]), $this->context);
        $operation->process(new DataItem(['id' => 3]), $this->context);
        $batch2 = $operation->process(new DataItem(['id' => 4]), $this->context);

        $this->assertEquals([['id' => 1], ['id' => 2]], $batch1->getData());
        $this->assertEquals([['id' => 3], ['id' => 4]], $batch2->getData());
    }

    public function testStopFlushesLeftoverPartialBatch(): void
    {
        $operation = new BatchOperation(new BatchConfig(size: 5));

        $operation->process(new DataItem(['id' => 1]), $this->context);
        $operation->process(new DataItem(['id' => 2]), $this->context);

        $result = $operation->processStop(new StopItem(), $this->context);

        $this->assertInstanceOf(DataItem::class, $result);
        $this->assertEquals([['id' => 1], ['id' => 2]], $result->getData());
    }

    public function testStopWithEmptyBufferPassesStopItemThrough(): void
    {
        $operation = new BatchOperation(new BatchConfig(size: 5));

        $stopItem = new StopItem();
        $result = $operation->processStop($stopItem, $this->context);

        $this->assertSame($stopItem, $result);
    }

    public function testStopAfterExactlyFullBatchesPassesStopItemThrough(): void
    {
        $operation = new BatchOperation(new BatchConfig(size: 2));

        $operation->process(new DataItem(['id' => 1]), $this->context);
        $operation->process(new DataItem(['id' => 2]), $this->context);

        $stopItem = new StopItem();
        $result = $operation->processStop($stopItem, $this->context);

        $this->assertSame($stopItem, $result);
    }

    public function testSecondStopCallAfterFlushPassesStopItemThrough(): void
    {
        $operation = new BatchOperation(new BatchConfig(size: 5));
        $operation->process(new DataItem(['id' => 1]), $this->context);

        $stopResult1 = $operation->processStop(new StopItem(), $this->context);
        $this->assertInstanceOf(DataItem::class, $stopResult1);

        $stopItem2 = new StopItem();
        $stopResult2 = $operation->processStop($stopItem2, $this->context);
        $this->assertSame($stopItem2, $stopResult2);
    }

    public function testBatchSizeOfOneEmitsEveryItemImmediately(): void
    {
        $operation = new BatchOperation(new BatchConfig(size: 1));

        $result = $operation->process(new DataItem(['id' => 1]), $this->context);

        $this->assertInstanceOf(DataItem::class, $result);
        $this->assertEquals([['id' => 1]], $result->getData());
    }

    public function testInvalidSizeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new BatchConfig(size: 0);
    }
}
