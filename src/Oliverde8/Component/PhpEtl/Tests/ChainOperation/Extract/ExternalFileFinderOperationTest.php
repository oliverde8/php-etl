<?php

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation\Extract;

use Oliverde8\Component\PhpEtl\ChainOperation\Extract\ExternalFileFinderOperation;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\ExternalFileItem;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\ExternalFileFinderConfig;
use PHPUnit\Framework\TestCase;

class ExternalFileFinderOperationTest extends TestCase
{
    public function testFindFilesWithSimplePattern()
    {
        $config = new ExternalFileFinderConfig('/data/files');

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('listContents')
            ->with('/data/files')
            ->willReturn(['file1.csv', 'file2.csv', 'file3.txt', 'file4.csv']);

        $operation = new ExternalFileFinderOperation($fileSystem, $config);

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem('/\.csv$/');

        $result = $operation->process($item, $context);

        $this->assertInstanceOf(MixItem::class, $result);

        $files = $result->getItems();
        $this->assertCount(3, $files);

        foreach ($files as $file) {
            $this->assertInstanceOf(ExternalFileItem::class, $file);
        }

        $this->assertEquals('/data/files/file1.csv', $files[0]->getFilePath());
        $this->assertEquals('/data/files/file2.csv', $files[1]->getFilePath());
        $this->assertEquals('/data/files/file4.csv', $files[2]->getFilePath());
    }

    public function testFindFilesWithNoMatches()
    {
        $config = new ExternalFileFinderConfig('/data/files');

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('listContents')
            ->with('/data/files')
            ->willReturn(['file1.txt', 'file2.doc', 'file3.pdf']);

        $operation = new ExternalFileFinderOperation($fileSystem, $config);

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem('/\.csv$/');

        $result = $operation->process($item, $context);

        $files = $result->getItems();
        $this->assertCount(0, $files);
    }

    public function testFindFilesWithComplexPattern()
    {
        $config = new ExternalFileFinderConfig('/data/files');

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('listContents')
            ->willReturn([
                'report_2023_01.csv',
                'report_2023_02.csv',
                'summary_2023_01.csv',
                'report_2024_01.csv',
                'data.csv'
            ]);

        $operation = new ExternalFileFinderOperation($fileSystem, $config);

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem('/^report_2023_/');

        $result = $operation->process($item, $context);

        $files = $result->getItems();
        $this->assertCount(2, $files);
        $this->assertEquals('/data/files/report_2023_01.csv', $files[0]->getFilePath());
        $this->assertEquals('/data/files/report_2023_02.csv', $files[1]->getFilePath());
    }

    public function testFindFilesInEmptyDirectory()
    {
        $config = new ExternalFileFinderConfig('/empty/dir');

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('listContents')
            ->with('/empty/dir')
            ->willReturn([]);

        $operation = new ExternalFileFinderOperation($fileSystem, $config);

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem('/.*$/');

        $result = $operation->process($item, $context);

        $files = $result->getItems();
        $this->assertCount(0, $files);
    }

    public function testFindAllFilesWithMatchAllPattern()
    {
        $config = new ExternalFileFinderConfig('/data');

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('listContents')
            ->willReturn(['file1.csv', 'file2.txt', 'file3.json']);

        $operation = new ExternalFileFinderOperation($fileSystem, $config);

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem('/.*$/');

        $result = $operation->process($item, $context);

        $files = $result->getItems();
        $this->assertCount(3, $files);
    }

    public function testFileSystemIsPassedToExternalFileItem()
    {
        $config = new ExternalFileFinderConfig('/data');

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('listContents')
            ->willReturn(['test.csv']);

        $operation = new ExternalFileFinderOperation($fileSystem, $config);

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem('/\.csv$/');

        $result = $operation->process($item, $context);

        $files = $result->getItems();
        $this->assertCount(1, $files);
        $this->assertSame($fileSystem, $files[0]->getFileSystem());
    }

    public function testExternalFileItemInitialState()
    {
        $config = new ExternalFileFinderConfig('/data');

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('listContents')
            ->willReturn(['test.csv']);

        $operation = new ExternalFileFinderOperation($fileSystem, $config);

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem('/\.csv$/');

        $result = $operation->process($item, $context);

        $files = $result->getItems();
        $this->assertEquals(ExternalFileItem::STATE_NEW, $files[0]->getState());
    }

    public function testFindFilesWithCaseInsensitivePattern()
    {
        $config = new ExternalFileFinderConfig('/data');

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('listContents')
            ->willReturn(['File1.CSV', 'file2.csv', 'FILE3.TXT']);

        $operation = new ExternalFileFinderOperation($fileSystem, $config);

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem('/\.csv$/i');

        $result = $operation->process($item, $context);

        $files = $result->getItems();
        $this->assertCount(2, $files);
        $this->assertEquals('/data/File1.CSV', $files[0]->getFilePath());
        $this->assertEquals('/data/file2.csv', $files[1]->getFilePath());
    }

    public function testFindFilesWithNumericPattern()
    {
        $config = new ExternalFileFinderConfig('/data');

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('listContents')
            ->willReturn(['file001.csv', 'file002.csv', 'fileabc.csv', 'file999.csv']);

        $operation = new ExternalFileFinderOperation($fileSystem, $config);

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem('/file\d+\.csv$/');

        $result = $operation->process($item, $context);

        $files = $result->getItems();
        $this->assertCount(3, $files);
        $this->assertEquals('/data/file001.csv', $files[0]->getFilePath());
        $this->assertEquals('/data/file002.csv', $files[1]->getFilePath());
        $this->assertEquals('/data/file999.csv', $files[2]->getFilePath());
    }

    public function testDirectoryWithExpression()
    {
        $config = new ExternalFileFinderConfig('@context["directory"]');

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('listContents')
            ->with('/dynamic/path')
            ->willReturn(['file1.csv', 'file2.csv']);

        $operation = new ExternalFileFinderOperation($fileSystem, $config);

        $context = new ExecutionContext(['directory' => '/dynamic/path'], $fileSystem);
        $item = new DataItem('/\.csv$/');

        $result = $operation->process($item, $context);

        $files = $result->getItems();
        $this->assertCount(2, $files);
        $this->assertEquals('/dynamic/path/file1.csv', $files[0]->getFilePath());
    }

    public function testDirectoryExpressionWithComplexPath()
    {
        $config = new ExternalFileFinderConfig('@context["base_path"] ~ "/" ~ context["sub_dir"]');

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('listContents')
            ->with('/base/subdir')
            ->willReturn(['test.csv']);

        $operation = new ExternalFileFinderOperation($fileSystem, $config);

        $context = new ExecutionContext(['base_path' => '/base', 'sub_dir' => 'subdir'], $fileSystem);
        $item = new DataItem('/\.csv$/');

        $result = $operation->process($item, $context);

        $files = $result->getItems();
        $this->assertCount(1, $files);
        $this->assertEquals('/base/subdir/test.csv', $files[0]->getFilePath());
    }

    public function testFindFilesWithSpecialCharactersInName()
    {
        $config = new ExternalFileFinderConfig('/data');

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('listContents')
            ->willReturn(['file-1.csv', 'file_2.csv', 'file.3.csv', 'file@4.csv']);

        $operation = new ExternalFileFinderOperation($fileSystem, $config);

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem('/file[-_.].*\.csv$/');

        $result = $operation->process($item, $context);

        $files = $result->getItems();
        $this->assertCount(3, $files);
    }

    public function testMultipleExtensionsPattern()
    {
        $config = new ExternalFileFinderConfig('/data');

        $fileSystem = $this->createMock(FileSystemInterface::class);
        $fileSystem->method('listContents')
            ->willReturn([
                'data.csv',
                'data.json',
                'data.xml',
                'data.txt',
                'data.xlsx'
            ]);

        $operation = new ExternalFileFinderOperation($fileSystem, $config);

        $context = new ExecutionContext([], $fileSystem);
        $item = new DataItem('/\.(csv|json|xml)$/');

        $result = $operation->process($item, $context);

        $files = $result->getItems();
        $this->assertCount(3, $files);
        $this->assertEquals('/data/data.csv', $files[0]->getFilePath());
        $this->assertEquals('/data/data.json', $files[1]->getFilePath());
        $this->assertEquals('/data/data.xml', $files[2]->getFilePath());
    }
}

