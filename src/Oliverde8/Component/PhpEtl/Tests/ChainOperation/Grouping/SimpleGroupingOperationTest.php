<?php

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation\Grouping;

use Oliverde8\Component\PhpEtl\ChainOperation\Grouping\SimpleGroupingOperation;
use Oliverde8\Component\PhpEtl\Item\ChainBreakItem;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\GroupedItem;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem;
use Oliverde8\Component\PhpEtl\OperationConfig\Grouping\SimpleGroupingConfig;
use PHPUnit\Framework\TestCase;

class SimpleGroupingOperationTest extends TestCase
{
    private ExecutionContext $context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->context = new ExecutionContext([], new LocalFileSystem());
    }

    public function testGroupBySingleKey()
    {
        $config = new SimpleGroupingConfig(['category']);
        $operation = new SimpleGroupingOperation($config);

        $item1 = new DataItem(['category' => 'A', 'value' => 1]);
        $item2 = new DataItem(['category' => 'B', 'value' => 2]);
        $item3 = new DataItem(['category' => 'A', 'value' => 3]);

        $result1 = $operation->process($item1, $this->context);
        $result2 = $operation->process($item2, $this->context);
        $result3 = $operation->process($item3, $this->context);

        $this->assertInstanceOf(ChainBreakItem::class, $result1);
        $this->assertInstanceOf(ChainBreakItem::class, $result2);
        $this->assertInstanceOf(ChainBreakItem::class, $result3);

        $stopResult = $operation->processStop(new StopItem(), $this->context);
        $this->assertInstanceOf(GroupedItem::class, $stopResult);

        $groupedData = iterator_to_array($stopResult->getIterator());

        $this->assertCount(2, $groupedData);
        $this->assertArrayHasKey('A', $groupedData);
        $this->assertArrayHasKey('B', $groupedData);
        $this->assertCount(2, $groupedData['A']);
        $this->assertCount(1, $groupedData['B']);
    }

    public function testGroupByNestedKey()
    {
        $config = new SimpleGroupingConfig(['user', 'type']);
        $operation = new SimpleGroupingOperation($config);

        $item1 = new DataItem(['user' => ['type' => 'admin'], 'name' => 'John']);
        $item2 = new DataItem(['user' => ['type' => 'user'], 'name' => 'Jane']);
        $item3 = new DataItem(['user' => ['type' => 'admin'], 'name' => 'Bob']);

        $operation->process($item1, $this->context);
        $operation->process($item2, $this->context);
        $operation->process($item3, $this->context);

        $stopResult = $operation->processStop(new StopItem(), $this->context);
        $groupedData = iterator_to_array($stopResult->getIterator());

        $this->assertCount(2, $groupedData);
        $this->assertArrayHasKey('admin', $groupedData);
        $this->assertArrayHasKey('user', $groupedData);
        $this->assertCount(2, $groupedData['admin']);
        $this->assertCount(1, $groupedData['user']);
    }

    public function testGroupWithIdentifierKey()
    {
        $config = new SimpleGroupingConfig(['category'], ['id']);
        $operation = new SimpleGroupingOperation($config);

        $item1 = new DataItem(['category' => 'A', 'id' => 1, 'value' => 'first']);
        $item2 = new DataItem(['category' => 'A', 'id' => 2, 'value' => 'second']);
        $item3 = new DataItem(['category' => 'B', 'id' => 1, 'value' => 'third']);

        $operation->process($item1, $this->context);
        $operation->process($item2, $this->context);
        $operation->process($item3, $this->context);

        $stopResult = $operation->processStop(new StopItem(), $this->context);
        $groupedData = iterator_to_array($stopResult->getIterator());

        $this->assertArrayHasKey('A', $groupedData);
        $this->assertArrayHasKey('B', $groupedData);
        $this->assertArrayHasKey(1, $groupedData['A']);
        $this->assertArrayHasKey(2, $groupedData['A']);
        $this->assertEquals('first', $groupedData['A'][1]['value']);
        $this->assertEquals('second', $groupedData['A'][2]['value']);
    }

    public function testGroupWithNestedIdentifierKey()
    {
        $config = new SimpleGroupingConfig(['category'], ['meta', 'id']);
        $operation = new SimpleGroupingOperation($config);

        $item1 = new DataItem(['category' => 'A', 'meta' => ['id' => 100], 'value' => 'test1']);
        $item2 = new DataItem(['category' => 'A', 'meta' => ['id' => 200], 'value' => 'test2']);

        $operation->process($item1, $this->context);
        $operation->process($item2, $this->context);

        $stopResult = $operation->processStop(new StopItem(), $this->context);
        $groupedData = iterator_to_array($stopResult->getIterator());

        $this->assertArrayHasKey(100, $groupedData['A']);
        $this->assertArrayHasKey(200, $groupedData['A']);
        $this->assertEquals('test1', $groupedData['A'][100]['value']);
    }

    public function testMultipleGroupsWithoutIdentifier()
    {
        $config = new SimpleGroupingConfig(['status']);
        $operation = new SimpleGroupingOperation($config);

        $item1 = new DataItem(['status' => 'active', 'name' => 'A']);
        $item2 = new DataItem(['status' => 'active', 'name' => 'B']);
        $item3 = new DataItem(['status' => 'inactive', 'name' => 'C']);

        $operation->process($item1, $this->context);
        $operation->process($item2, $this->context);
        $operation->process($item3, $this->context);

        $stopResult = $operation->processStop(new StopItem(), $this->context);
        $groupedData = iterator_to_array($stopResult->getIterator());

        $this->assertCount(2, $groupedData['active']);
        $this->assertCount(1, $groupedData['inactive']);
        $this->assertEquals('A', $groupedData['active'][0]['name']);
        $this->assertEquals('B', $groupedData['active'][1]['name']);
    }

    public function testEmptyDataReturnsStopItem()
    {
        $config = new SimpleGroupingConfig(['category']);
        $operation = new SimpleGroupingOperation($config);

        $stopItem = new StopItem();
        $result = $operation->processStop($stopItem, $this->context);

        $this->assertSame($stopItem, $result);
    }

    public function testDataClearedAfterStop()
    {
        $config = new SimpleGroupingConfig(['type']);
        $operation = new SimpleGroupingOperation($config);

        $item1 = new DataItem(['type' => 'X', 'value' => 1]);
        $operation->process($item1, $this->context);

        $stopResult1 = $operation->processStop(new StopItem(), $this->context);
        $groupedData1 = iterator_to_array($stopResult1->getIterator());
        $this->assertCount(1, $groupedData1);

        $stopResult2 = $operation->processStop(new StopItem(), $this->context);
        $this->assertInstanceOf(StopItem::class, $stopResult2);
    }

    public function testMultipleStopCycles()
    {
        $config = new SimpleGroupingConfig(['batch']);
        $operation = new SimpleGroupingOperation($config);

        $item1 = new DataItem(['batch' => 1, 'value' => 'a']);
        $operation->process($item1, $this->context);
        $stopResult1 = $operation->processStop(new StopItem(), $this->context);
        $groupedData1 = iterator_to_array($stopResult1->getIterator());
        $this->assertArrayHasKey(1, $groupedData1);

        $item2 = new DataItem(['batch' => 2, 'value' => 'b']);
        $operation->process($item2, $this->context);
        $stopResult2 = $operation->processStop(new StopItem(), $this->context);
        $groupedData2 = iterator_to_array($stopResult2->getIterator());
        $this->assertArrayHasKey(2, $groupedData2);
        $this->assertArrayNotHasKey(1, $groupedData2);
    }

    public function testGroupingPreservesAllDataFields()
    {
        $config = new SimpleGroupingConfig(['category']);
        $operation = new SimpleGroupingOperation($config);

        $item = new DataItem([
            'category' => 'test',
            'id' => 123,
            'name' => 'Test Item',
            'metadata' => ['key' => 'value']
        ]);

        $operation->process($item, $this->context);
        $stopResult = $operation->processStop(new StopItem(), $this->context);
        $groupedData = iterator_to_array($stopResult->getIterator());

        $this->assertEquals(123, $groupedData['test'][0]['id']);
        $this->assertEquals('Test Item', $groupedData['test'][0]['name']);
        $this->assertEquals(['key' => 'value'], $groupedData['test'][0]['metadata']);
    }

    public function testOverwriteWithSameIdentifier()
    {
        $config = new SimpleGroupingConfig(['category'], ['id']);
        $operation = new SimpleGroupingOperation($config);

        $item1 = new DataItem(['category' => 'A', 'id' => 1, 'value' => 'first']);
        $item2 = new DataItem(['category' => 'A', 'id' => 1, 'value' => 'second']);

        $operation->process($item1, $this->context);
        $operation->process($item2, $this->context);

        $stopResult = $operation->processStop(new StopItem(), $this->context);
        $groupedData = iterator_to_array($stopResult->getIterator());

        $this->assertCount(1, $groupedData['A']);
        $this->assertEquals('second', $groupedData['A'][1]['value']);
    }

    public function testNumericGroupKeys()
    {
        $config = new SimpleGroupingConfig(['priority']);
        $operation = new SimpleGroupingOperation($config);

        $item1 = new DataItem(['priority' => 1, 'task' => 'high']);
        $item2 = new DataItem(['priority' => 2, 'task' => 'medium']);
        $item3 = new DataItem(['priority' => 1, 'task' => 'critical']);

        $operation->process($item1, $this->context);
        $operation->process($item2, $this->context);
        $operation->process($item3, $this->context);

        $stopResult = $operation->processStop(new StopItem(), $this->context);
        $groupedData = iterator_to_array($stopResult->getIterator());

        $this->assertArrayHasKey(1, $groupedData);
        $this->assertArrayHasKey(2, $groupedData);
        $this->assertCount(2, $groupedData[1]);
        $this->assertCount(1, $groupedData[2]);
    }
}

