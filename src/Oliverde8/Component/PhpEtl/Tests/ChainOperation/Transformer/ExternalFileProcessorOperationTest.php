<?php

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation\Transformer;

use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\ExternalFileProcessorOperation;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\ExternalFileItem;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\ExternalFileProcessorConfig;
use PHPUnit\Framework\TestCase;

class ExternalFileProcessorOperationTest extends TestCase
{
    public function testProcessNewFileMovesToProcessing()
    {
        $config = new ExternalFileProcessorConfig();
        $operation = new ExternalFileProcessorOperation($config);

        $externalFileSystem = $this->createMock(FileSystemInterface::class);
        $localFileSystem = $this->createMock(FileSystemInterface::class);

        $externalFileSystem->expects($this->once())
            ->method('createDirectory')
            ->with('/data/files/processing');

        $externalFileSystem->expects($this->once())
            ->method('move')
            ->with('/data/files/test.csv', '/data/files/processing/test.csv');

        $externalFileSystem->expects($this->once())
            ->method('readStream')
            ->with('/data/files/processing/test.csv')
            ->willReturn(fopen('php://memory', 'r'));

        $localFileSystem->expects($this->once())
            ->method('writeStream')
            ->with('test.csv', $this->anything());

        $context = new ExecutionContext([], $localFileSystem);
        $item = new ExternalFileItem('/data/files/test.csv', $externalFileSystem);

        $this->assertEquals(ExternalFileItem::STATE_NEW, $item->getState());

        $result = $operation->processFile($item, $context);

        $this->assertInstanceOf(MixItem::class, $result);
        $items = $result->getItems();
        $this->assertCount(2, $items);
        $this->assertInstanceOf(DataItem::class, $items[0]);
        $this->assertInstanceOf(ExternalFileItem::class, $items[1]);
        $this->assertEquals('test.csv', $items[0]->getData());
        $this->assertEquals(ExternalFileItem::STATE_PROCESSING, $item->getState());
    }

    public function testProcessProcessingFileMovesToProcessed()
    {
        $config = new ExternalFileProcessorConfig();
        $operation = new ExternalFileProcessorOperation($config);

        $externalFileSystem = $this->createMock(FileSystemInterface::class);
        $localFileSystem = $this->createMock(FileSystemInterface::class);

        $externalFileSystem->expects($this->once())
            ->method('createDirectory')
            ->with('/data/files/processed');

        $externalFileSystem->expects($this->once())
            ->method('move')
            ->with('/data/files/processing/test.csv', '/data/files/processed/test.csv');

        $context = new ExecutionContext([], $localFileSystem);
        $item = new ExternalFileItem('/data/files/test.csv', $externalFileSystem);
        $item->setState(ExternalFileItem::STATE_PROCESSING);

        $result = $operation->processFile($item, $context);

        $this->assertInstanceOf(ExternalFileItem::class, $result);
        $this->assertEquals(ExternalFileItem::STATE_PROCESSED, $item->getState());
    }

    public function testProcessNewFileCreatesLocalCopy()
    {
        $config = new ExternalFileProcessorConfig();
        $operation = new ExternalFileProcessorOperation($config);

        $externalFileSystem = $this->createMock(FileSystemInterface::class);
        $localFileSystem = $this->createMock(FileSystemInterface::class);

        $fileContent = 'test,content';
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $fileContent);
        rewind($stream);

        $externalFileSystem->method('readStream')->willReturn($stream);
        $externalFileSystem->method('createDirectory');
        $externalFileSystem->method('move');

        $capturedStream = null;
        $localFileSystem->expects($this->once())
            ->method('writeStream')
            ->with('test.csv', $this->anything())
            ->willReturnCallback(function($filename, $stream) use (&$capturedStream) {
                $capturedStream = $stream;
            });

        $context = new ExecutionContext([], $localFileSystem);
        $item = new ExternalFileItem('/data/files/test.csv', $externalFileSystem);

        $operation->processFile($item, $context);

        $this->assertNotNull($capturedStream);
    }

    public function testProcessFileWithNestedDirectory()
    {
        $config = new ExternalFileProcessorConfig();
        $operation = new ExternalFileProcessorOperation($config);

        $externalFileSystem = $this->createMock(FileSystemInterface::class);
        $localFileSystem = $this->createMock(FileSystemInterface::class);

        $externalFileSystem->expects($this->once())
            ->method('createDirectory')
            ->with('/data/nested/dir/processing');

        $externalFileSystem->expects($this->once())
            ->method('move')
            ->with('/data/nested/dir/file.csv', '/data/nested/dir/processing/file.csv');

        $externalFileSystem->method('readStream')->willReturn(fopen('php://memory', 'r'));
        $localFileSystem->method('writeStream');

        $context = new ExecutionContext([], $localFileSystem);
        $item = new ExternalFileItem('/data/nested/dir/file.csv', $externalFileSystem);

        $result = $operation->processFile($item, $context);

        $items = $result->getItems();
        $this->assertEquals('file.csv', $items[0]->getData());
    }

    public function testProcessFilePreservesFilename()
    {
        $config = new ExternalFileProcessorConfig();
        $operation = new ExternalFileProcessorOperation($config);

        $externalFileSystem = $this->createMock(FileSystemInterface::class);
        $localFileSystem = $this->createMock(FileSystemInterface::class);

        $externalFileSystem->method('createDirectory');
        $externalFileSystem->method('move');
        $externalFileSystem->method('readStream')->willReturn(fopen('php://memory', 'r'));
        $localFileSystem->method('writeStream');

        $context = new ExecutionContext([], $localFileSystem);
        $item = new ExternalFileItem('/data/special-file_name.123.csv', $externalFileSystem);

        $result = $operation->processFile($item, $context);

        $items = $result->getItems();
        $this->assertEquals('special-file_name.123.csv', $items[0]->getData());
    }

    public function testProcessMultipleFilesInSequence()
    {
        $config = new ExternalFileProcessorConfig();
        $operation = new ExternalFileProcessorOperation($config);

        $externalFileSystem = $this->createMock(FileSystemInterface::class);
        $localFileSystem = $this->createMock(FileSystemInterface::class);

        $externalFileSystem->method('createDirectory');
        $externalFileSystem->method('move');
        $externalFileSystem->method('readStream')->willReturn(fopen('php://memory', 'r'));
        $localFileSystem->method('writeStream');

        $context = new ExecutionContext([], $localFileSystem);

        $item1 = new ExternalFileItem('/data/file1.csv', $externalFileSystem);
        $result1 = $operation->processFile($item1, $context);
        $this->assertEquals(ExternalFileItem::STATE_PROCESSING, $item1->getState());

        $result2 = $operation->processFile($item1, $context);
        $this->assertEquals(ExternalFileItem::STATE_PROCESSED, $item1->getState());
        $this->assertInstanceOf(ExternalFileItem::class, $result2);
    }

    public function testNewFileResultContainsDataItemAndExternalFileItem()
    {
        $config = new ExternalFileProcessorConfig();
        $operation = new ExternalFileProcessorOperation($config);

        $externalFileSystem = $this->createMock(FileSystemInterface::class);
        $localFileSystem = $this->createMock(FileSystemInterface::class);

        $externalFileSystem->method('createDirectory');
        $externalFileSystem->method('move');
        $externalFileSystem->method('readStream')->willReturn(fopen('php://memory', 'r'));
        $localFileSystem->method('writeStream');

        $context = new ExecutionContext([], $localFileSystem);
        $item = new ExternalFileItem('/data/test.csv', $externalFileSystem);

        $result = $operation->processFile($item, $context);

        $this->assertInstanceOf(MixItem::class, $result);
        $items = $result->getItems();
        $this->assertCount(2, $items);
        $this->assertSame($item, $items[1]);
    }

    public function testProcessingFileOnlyReturnsExternalFileItem()
    {
        $config = new ExternalFileProcessorConfig();
        $operation = new ExternalFileProcessorOperation($config);

        $externalFileSystem = $this->createMock(FileSystemInterface::class);
        $localFileSystem = $this->createMock(FileSystemInterface::class);

        $externalFileSystem->method('createDirectory');
        $externalFileSystem->method('move');

        $context = new ExecutionContext([], $localFileSystem);
        $item = new ExternalFileItem('/data/test.csv', $externalFileSystem);
        $item->setState(ExternalFileItem::STATE_PROCESSING);

        $result = $operation->processFile($item, $context);

        $this->assertInstanceOf(ExternalFileItem::class, $result);
        $this->assertNotInstanceOf(MixItem::class, $result);
        $this->assertSame($item, $result);
    }

    public function testProcessFileCreatesProcessingDirectory()
    {
        $config = new ExternalFileProcessorConfig();
        $operation = new ExternalFileProcessorOperation($config);

        $externalFileSystem = $this->createMock(FileSystemInterface::class);
        $localFileSystem = $this->createMock(FileSystemInterface::class);

        $externalFileSystem->expects($this->once())
            ->method('createDirectory')
            ->with('/path/to/files/processing');

        $externalFileSystem->method('move');
        $externalFileSystem->method('readStream')->willReturn(fopen('php://memory', 'r'));
        $localFileSystem->method('writeStream');

        $context = new ExecutionContext([], $localFileSystem);
        $item = new ExternalFileItem('/path/to/files/myfile.csv', $externalFileSystem);

        $operation->processFile($item, $context);
    }

    public function testProcessFileCreatesProcessedDirectory()
    {
        $config = new ExternalFileProcessorConfig();
        $operation = new ExternalFileProcessorOperation($config);

        $externalFileSystem = $this->createMock(FileSystemInterface::class);
        $localFileSystem = $this->createMock(FileSystemInterface::class);

        $externalFileSystem->expects($this->once())
            ->method('createDirectory')
            ->with('/path/to/files/processed');

        $externalFileSystem->method('move');

        $context = new ExecutionContext([], $localFileSystem);
        $item = new ExternalFileItem('/path/to/files/myfile.csv', $externalFileSystem);
        $item->setState(ExternalFileItem::STATE_PROCESSING);

        $operation->processFile($item, $context);
    }

    public function testStateTransitionFromNewToProcessing()
    {
        $config = new ExternalFileProcessorConfig();
        $operation = new ExternalFileProcessorOperation($config);

        $externalFileSystem = $this->createMock(FileSystemInterface::class);
        $localFileSystem = $this->createMock(FileSystemInterface::class);

        $externalFileSystem->method('createDirectory');
        $externalFileSystem->method('move');
        $externalFileSystem->method('readStream')->willReturn(fopen('php://memory', 'r'));
        $localFileSystem->method('writeStream');

        $context = new ExecutionContext([], $localFileSystem);
        $item = new ExternalFileItem('/data/test.csv', $externalFileSystem);

        $initialState = $item->getState();
        $this->assertEquals(ExternalFileItem::STATE_NEW, $initialState);

        $operation->processFile($item, $context);

        $this->assertEquals(ExternalFileItem::STATE_PROCESSING, $item->getState());
    }

    public function testStateTransitionFromProcessingToProcessed()
    {
        $config = new ExternalFileProcessorConfig();
        $operation = new ExternalFileProcessorOperation($config);

        $externalFileSystem = $this->createMock(FileSystemInterface::class);
        $localFileSystem = $this->createMock(FileSystemInterface::class);

        $externalFileSystem->method('createDirectory');
        $externalFileSystem->method('move');

        $context = new ExecutionContext([], $localFileSystem);
        $item = new ExternalFileItem('/data/test.csv', $externalFileSystem);
        $item->setState(ExternalFileItem::STATE_PROCESSING);

        $initialState = $item->getState();
        $this->assertEquals(ExternalFileItem::STATE_PROCESSING, $initialState);

        $operation->processFile($item, $context);

        $this->assertEquals(ExternalFileItem::STATE_PROCESSED, $item->getState());
    }

    public function testFilenameExtraction()
    {
        $config = new ExternalFileProcessorConfig();
        $operation = new ExternalFileProcessorOperation($config);

        $externalFileSystem = $this->createMock(FileSystemInterface::class);
        $localFileSystem = $this->createMock(FileSystemInterface::class);

        $externalFileSystem->method('createDirectory');
        $externalFileSystem->method('move');
        $externalFileSystem->method('readStream')->willReturn(fopen('php://memory', 'r'));

        $capturedFilename = null;
        $localFileSystem->expects($this->once())
            ->method('writeStream')
            ->willReturnCallback(function($filename) use (&$capturedFilename) {
                $capturedFilename = $filename;
            });

        $context = new ExecutionContext([], $localFileSystem);
        $item = new ExternalFileItem('/very/deep/path/to/important-file.csv', $externalFileSystem);

        $operation->processFile($item, $context);

        $this->assertEquals('important-file.csv', $capturedFilename);
    }
}

