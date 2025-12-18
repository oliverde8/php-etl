<?php

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation\Extract;

use Oliverde8\Component\PhpEtl\ChainOperation\Extract\CsvExtractOperation;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\FileExtractedItem;
use Oliverde8\Component\PhpEtl\Item\GroupedItem;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use PHPUnit\Framework\TestCase;

class CsvExtractOperationTest extends TestCase
{
    private string $testCsvFile;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->testCsvFile = tempnam(sys_get_temp_dir(), 'csv_test_');
    }

    #[\Override]
    protected function tearDown(): void
    {
        if (file_exists($this->testCsvFile)) {
            unlink($this->testCsvFile);
        }
        parent::tearDown();
    }

    public function testExtractCsvWithDefaultDelimiter()
    {
        file_put_contents($this->testCsvFile, "name;age;city\nJohn;30;NYC\nJane;25;LA");

        $config = new CsvExtractConfig();
        $operation = new CsvExtractOperation($config);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('readStream')->willReturn(fopen($this->testCsvFile, 'r'));

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem($this->testCsvFile);

        $result = $operation->process($item, $context);

        $this->assertInstanceOf(MixItem::class, $result);

        $items = $result->getItems();
        $this->assertCount(2, $items);
        $this->assertInstanceOf(GroupedItem::class, $items[0]);
        $this->assertInstanceOf(FileExtractedItem::class, $items[1]);

        $csvData = iterator_to_array($items[0]->getIterator(), false);
        $this->assertIsArray($csvData);
        $this->assertCount(2, $csvData, 'Expected 2 rows of CSV data');
        $this->assertArrayHasKey(0, $csvData);
        $this->assertEquals(['name' => 'John', 'age' => '30', 'city' => 'NYC'], $csvData[0]);
        $this->assertEquals(['name' => 'Jane', 'age' => '25', 'city' => 'LA'], $csvData[1]);
    }

    public function testExtractCsvWithCustomDelimiter()
    {
        file_put_contents($this->testCsvFile, "name,age,city\nJohn,30,NYC\nJane,25,LA");

        $config = new CsvExtractConfig(delimiter: ',');
        $operation = new CsvExtractOperation($config);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('readStream')->willReturn(fopen($this->testCsvFile, 'r'));

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem($this->testCsvFile);

        $result = $operation->process($item, $context);
        $items = $result->getItems();
        $csvData = iterator_to_array($items[0]->getIterator(), false);

        $this->assertCount(2, $csvData);
        $this->assertEquals('John', $csvData[0]['name']);
        $this->assertEquals('30', $csvData[0]['age']);
    }

    public function testExtractCsvWithCustomEnclosure()
    {
        file_put_contents($this->testCsvFile, "'name';'age';'city'\n'John';'30';'NYC'");

        $config = new CsvExtractConfig(enclosure: "'");
        $operation = new CsvExtractOperation($config);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('readStream')->willReturn(fopen($this->testCsvFile, 'r'));

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem($this->testCsvFile);

        $result = $operation->process($item, $context);
        $items = $result->getItems();
        $csvData = iterator_to_array($items[0]->getIterator(), false);

        $this->assertEquals('John', $csvData[0]['name']);
    }

    public function testExtractCsvFromArrayWithFileKey()
    {
        file_put_contents($this->testCsvFile, "name;age\nJohn;30");

        $config = new CsvExtractConfig(fileKey: 'filepath');
        $operation = new CsvExtractOperation($config);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('readStream')->willReturn(fopen($this->testCsvFile, 'r'));

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem(['filepath' => $this->testCsvFile, 'other' => 'data']);

        $result = $operation->process($item, $context);
        $items = $result->getItems();
        $csvData = iterator_to_array($items[0]->getIterator(), false);

        $this->assertCount(1, $csvData);
        $this->assertEquals('John', $csvData[0]['name']);
    }

    public function testExtractCsvWithNestedFileKey()
    {
        file_put_contents($this->testCsvFile, "name;age\nJohn;30");

        $config = new CsvExtractConfig(fileKey: 'data/file');
        $operation = new CsvExtractOperation($config);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('readStream')->willReturn(fopen($this->testCsvFile, 'r'));

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem(['data' => ['file' => $this->testCsvFile]]);

        $result = $operation->process($item, $context);
        $items = $result->getItems();
        $csvData = iterator_to_array($items[0]->getIterator(), false);

        $this->assertCount(1, $csvData);
    }

    public function testExtractEmptyCsv()
    {
        file_put_contents($this->testCsvFile, "name;age\n");

        $config = new CsvExtractConfig();
        $operation = new CsvExtractOperation($config);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('readStream')->willReturn(fopen($this->testCsvFile, 'r'));

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem($this->testCsvFile);

        $result = $operation->process($item, $context);
        $items = $result->getItems();
        $csvData = iterator_to_array($items[0]->getIterator(), false);

        $this->assertCount(0, $csvData);
    }

    public function testExtractCsvWithQuotedFields()
    {
        file_put_contents($this->testCsvFile, "name;description\n\"John\";\";special;chars;\"\n");

        $config = new CsvExtractConfig();
        $operation = new CsvExtractOperation($config);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('readStream')->willReturn(fopen($this->testCsvFile, 'r'));

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem($this->testCsvFile);

        $result = $operation->process($item, $context);
        $items = $result->getItems();
        $csvData = iterator_to_array($items[0]->getIterator(), false);

        $this->assertEquals('John', $csvData[0]['name']);
        $this->assertEquals(';special;chars;', $csvData[0]['description']);
    }

    public function testFileExtractedItemContainsCorrectFilename()
    {
        file_put_contents($this->testCsvFile, "name;age\nJohn;30");

        $config = new CsvExtractConfig();
        $operation = new CsvExtractOperation($config);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('readStream')->willReturn(fopen($this->testCsvFile, 'r'));

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem($this->testCsvFile);

        $result = $operation->process($item, $context);
        $items = $result->getItems();

        $this->assertInstanceOf(FileExtractedItem::class, $items[1]);
        $this->assertEquals($this->testCsvFile, $items[1]->getFilePath());
    }

    public function testExtractMultipleRows()
    {
        $csvContent = "id;name;email\n1;John;john@test.com\n2;Jane;jane@test.com\n3;Bob;bob@test.com";
        file_put_contents($this->testCsvFile, $csvContent);

        $config = new CsvExtractConfig();
        $operation = new CsvExtractOperation($config);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('readStream')->willReturn(fopen($this->testCsvFile, 'r'));

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem($this->testCsvFile);

        $result = $operation->process($item, $context);
        $items = $result->getItems();
        $csvData = iterator_to_array($items[0]->getIterator(), false);

        $this->assertCount(3, $csvData);
        $this->assertEquals('1', $csvData[0]['id']);
        $this->assertEquals('2', $csvData[1]['id']);
        $this->assertEquals('3', $csvData[2]['id']);
    }

    public function testExtractCsvWithEscapeCharacter()
    {
        file_put_contents($this->testCsvFile, "name;value\n\"test\";\"escaped\\\"quote\"");

        $config = new CsvExtractConfig(escape: '\\');
        $operation = new CsvExtractOperation($config);

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('readStream')->willReturn(fopen($this->testCsvFile, 'r'));

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem($this->testCsvFile);

        $result = $operation->process($item, $context);
        $items = $result->getItems();
        $csvData = iterator_to_array($items[0]->getIterator(), false);

        $this->assertCount(1, $csvData);
        $this->assertEquals('test', $csvData[0]['name']);
    }

    public function testGetConfigurationClass()
    {
        $config = new CsvExtractConfig();
        $operation = new CsvExtractOperation($config);

        $this->assertEquals(CsvExtractConfig::class, $operation->getConfigurationClass());
    }
}

