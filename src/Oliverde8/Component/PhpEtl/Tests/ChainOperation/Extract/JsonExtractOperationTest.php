<?php

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation\Extract;

use Oliverde8\Component\PhpEtl\ChainOperation\Extract\JsonExtractOperation;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\FileExtractedItem;
use Oliverde8\Component\PhpEtl\Item\GroupedItem;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\JsonExtractConfig;
use PHPUnit\Framework\TestCase;

class JsonExtractOperationTest extends TestCase
{
    private string $testJsonFile;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->testJsonFile = tempnam(sys_get_temp_dir(), 'json_test_');
    }

    #[\Override]
    protected function tearDown(): void
    {
        if (file_exists($this->testJsonFile)) {
            unlink($this->testJsonFile);
        }
        parent::tearDown();
    }

    public function testExtractSimpleJsonArray()
    {
        $jsonData = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane']
        ];
        file_put_contents($this->testJsonFile, json_encode($jsonData));

        $config = new JsonExtractConfig();
        $operation = new JsonExtractOperation($config);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('read')->willReturn(file_get_contents($this->testJsonFile));

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem($this->testJsonFile);

        $result = $operation->process($item, $context);

        $this->assertInstanceOf(MixItem::class, $result);

        $items = $result->getItems();
        $this->assertCount(2, $items);
        $this->assertInstanceOf(GroupedItem::class, $items[0]);
        $this->assertInstanceOf(FileExtractedItem::class, $items[1]);

        $extractedData = iterator_to_array($items[0]->getIterator(), false);
        $this->assertCount(2, $extractedData);
        $this->assertEquals(['id' => 1, 'name' => 'John'], $extractedData[0]);
        $this->assertEquals(['id' => 2, 'name' => 'Jane'], $extractedData[1]);
    }

    public function testExtractJsonObject()
    {
        $jsonData = ['user' => 'John', 'age' => 30, 'active' => true];
        file_put_contents($this->testJsonFile, json_encode($jsonData));

        $config = new JsonExtractConfig();
        $operation = new JsonExtractOperation($config);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('read')->willReturn(file_get_contents($this->testJsonFile));

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem($this->testJsonFile);

        $result = $operation->process($item, $context);
        $items = $result->getItems();
        $extractedData = iterator_to_array($items[0]->getIterator(), false);

        $this->assertEquals('John', $extractedData[0]);
        $this->assertEquals(30, $extractedData[1]);
        $this->assertTrue($extractedData[2]);
    }

    public function testExtractFromArrayWithFileKey()
    {
        $jsonData = [['name' => 'Test']];
        file_put_contents($this->testJsonFile, json_encode($jsonData));

        $config = new JsonExtractConfig(fileKey: 'filepath');
        $operation = new JsonExtractOperation($config);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('read')->willReturn(file_get_contents($this->testJsonFile));

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem(['filepath' => $this->testJsonFile, 'other' => 'data']);

        $result = $operation->process($item, $context);
        $items = $result->getItems();
        $extractedData = iterator_to_array($items[0]->getIterator(), false);

        $this->assertCount(1, $extractedData);
        $this->assertEquals('Test', $extractedData[0]['name']);
    }

    public function testExtractWithNestedFileKey()
    {
        $jsonData = [['id' => 1]];
        file_put_contents($this->testJsonFile, json_encode($jsonData));

        $config = new JsonExtractConfig(fileKey: 'data/file');
        $operation = new JsonExtractOperation($config);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('read')->willReturn(file_get_contents($this->testJsonFile));

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem(['data' => ['file' => $this->testJsonFile]]);

        $result = $operation->process($item, $context);
        $items = $result->getItems();
        $extractedData = iterator_to_array($items[0]->getIterator(), false);

        $this->assertCount(1, $extractedData);
        $this->assertEquals(1, $extractedData[0]['id']);
    }

    public function testExtractEmptyJsonArray()
    {
        file_put_contents($this->testJsonFile, json_encode([]));

        $config = new JsonExtractConfig();
        $operation = new JsonExtractOperation($config);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('read')->willReturn(file_get_contents($this->testJsonFile));

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem($this->testJsonFile);

        $result = $operation->process($item, $context);
        $items = $result->getItems();
        $extractedData = iterator_to_array($items[0]->getIterator(), false);

        $this->assertCount(0, $extractedData);
    }

    public function testExtractNestedJsonStructure()
    {
        $jsonData = [
            [
                'id' => 1,
                'details' => ['address' => 'NYC', 'zip' => '10001'],
                'tags' => ['premium', 'active']
            ],
            [
                'id' => 2,
                'details' => ['address' => 'LA', 'zip' => '90001'],
                'tags' => ['basic']
            ]
        ];
        file_put_contents($this->testJsonFile, json_encode($jsonData));

        $config = new JsonExtractConfig();
        $operation = new JsonExtractOperation($config);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('read')->willReturn(file_get_contents($this->testJsonFile));

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem($this->testJsonFile);

        $result = $operation->process($item, $context);
        $items = $result->getItems();
        $extractedData = iterator_to_array($items[0]->getIterator(), false);

        $this->assertCount(2, $extractedData);
        $this->assertEquals('NYC', $extractedData[0]['details']['address']);
        $this->assertEquals(['premium', 'active'], $extractedData[0]['tags']);
        $this->assertEquals('LA', $extractedData[1]['details']['address']);
    }

    public function testExtractJsonWithSpecialCharacters()
    {
        $jsonData = [
            ['name' => 'Test "quoted"', 'description' => "Line1\nLine2"],
            ['name' => 'Test\'s', 'description' => 'Special: €, £, ¥']
        ];
        file_put_contents($this->testJsonFile, json_encode($jsonData));

        $config = new JsonExtractConfig();
        $operation = new JsonExtractOperation($config);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('read')->willReturn(file_get_contents($this->testJsonFile));

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem($this->testJsonFile);

        $result = $operation->process($item, $context);
        $items = $result->getItems();
        $extractedData = iterator_to_array($items[0]->getIterator(), false);

        $this->assertEquals('Test "quoted"', $extractedData[0]['name']);
        $this->assertEquals("Line1\nLine2", $extractedData[0]['description']);
        $this->assertEquals('Special: €, £, ¥', $extractedData[1]['description']);
    }

    public function testFileExtractedItemContainsOriginalData()
    {
        $jsonData = [['test' => 'value']];
        file_put_contents($this->testJsonFile, json_encode($jsonData));

        $config = new JsonExtractConfig();
        $operation = new JsonExtractOperation($config);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('read')->willReturn(file_get_contents($this->testJsonFile));

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem($this->testJsonFile);

        $result = $operation->process($item, $context);
        $items = $result->getItems();

        $this->assertInstanceOf(FileExtractedItem::class, $items[1]);
        $this->assertEquals($this->testJsonFile, $items[1]->getFilePath());
    }

    public function testFileExtractedItemWithArrayInput()
    {
        $jsonData = [['test' => 'value']];
        file_put_contents($this->testJsonFile, json_encode($jsonData));

        $config = new JsonExtractConfig(fileKey: 'file');
        $operation = new JsonExtractOperation($config);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('read')->willReturn(file_get_contents($this->testJsonFile));

        $context = new ExecutionContext([], $fileSystem);
        $inputData = ['file' => $this->testJsonFile, 'metadata' => 'info'];
        $item = new DataItem($inputData);

        $result = $operation->process($item, $context);
        $items = $result->getItems();

        $this->assertInstanceOf(FileExtractedItem::class, $items[1]);
        $this->assertEquals($this->testJsonFile, $items[1]->getFilePath());
    }

    public function testExtractJsonWithNumericKeys()
    {
        $jsonData = ['first' => 1, 'second' => 2, 'third' => 3];
        file_put_contents($this->testJsonFile, json_encode($jsonData));

        $config = new JsonExtractConfig();
        $operation = new JsonExtractOperation($config);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('read')->willReturn(file_get_contents($this->testJsonFile));

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem($this->testJsonFile);

        $result = $operation->process($item, $context);
        $items = $result->getItems();
        $extractedData = iterator_to_array($items[0]->getIterator(), false);

        $this->assertCount(3, $extractedData);
        $this->assertEquals(1, $extractedData[0]);
        $this->assertEquals(2, $extractedData[1]);
        $this->assertEquals(3, $extractedData[2]);
    }

    public function testExtractJsonWithBooleanAndNull()
    {
        $jsonData = [
            ['active' => true, 'deleted' => false, 'optional' => null]
        ];
        file_put_contents($this->testJsonFile, json_encode($jsonData));

        $config = new JsonExtractConfig();
        $operation = new JsonExtractOperation($config);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('read')->willReturn(file_get_contents($this->testJsonFile));

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem($this->testJsonFile);

        $result = $operation->process($item, $context);
        $items = $result->getItems();
        $extractedData = iterator_to_array($items[0]->getIterator(), false);

        $this->assertTrue($extractedData[0]['active']);
        $this->assertFalse($extractedData[0]['deleted']);
        $this->assertNull($extractedData[0]['optional']);
    }

    public function testExtractLargeJsonArray()
    {
        $jsonData = [];
        for ($i = 0; $i < 100; $i++) {
            $jsonData[] = ['id' => $i, 'value' => 'item_' . $i];
        }
        file_put_contents($this->testJsonFile, json_encode($jsonData));

        $config = new JsonExtractConfig();
        $operation = new JsonExtractOperation($config);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('read')->willReturn(file_get_contents($this->testJsonFile));

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem($this->testJsonFile);

        $result = $operation->process($item, $context);
        $items = $result->getItems();
        $extractedData = iterator_to_array($items[0]->getIterator(), false);

        $this->assertCount(100, $extractedData);
        $this->assertEquals(0, $extractedData[0]['id']);
        $this->assertEquals(99, $extractedData[99]['id']);
        $this->assertEquals('item_50', $extractedData[50]['value']);
    }
}

