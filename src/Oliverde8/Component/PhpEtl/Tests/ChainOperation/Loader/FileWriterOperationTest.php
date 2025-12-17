<?php
/**
 * File FileWriterOperationTest.php
 *
 * @author    de Cramer Oliver<oldec@smile.fr>
 * @copyright 2018 Smile
 */

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation\Loader;

use Oliverde8\Component\PhpEtl\ChainOperation\Loader\FileWriterOperation;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Load\File\Csv;
use Oliverde8\Component\PhpEtl\Load\File\FileWriterInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FileWriterOperationTest extends TestCase
{
    /** @var MockObject */
    protected $writerMock;

    /** @var FileWriterOperation */
    protected $writerOperation;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->writerMock = $this->getMockBuilder(FileWriterInterface::class)
            ->getMock();

        $this->writerOperation = new FileWriterOperation($this->writerMock, 'fileName');
    }

    public function testWrite()
    {
        $this->writerMock
            ->expects($this->exactly(1))
            ->method('write')
            ->with(['test']);
        $context = $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()->getMock();

        $this->writerOperation->process(new DataItem(['test']), $context);
    }

    public function testTmpFileDeleted()
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'etl');
        $csv = new Csv($tmpFile);
        $operation = new FileWriterOperation($csv, basename($tmpFile));
        $context = $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()->getMock();

        $operation->process(new DataItem(['test' => 'val1']), $context);
        $this->assertTrue(file_exists($tmpFile), "Expecting temporary file {$tmpFile} to be created");

        $operation->processStop(new StopItem(false), $context);
        $this->assertTrue(file_exists($tmpFile), "Expecting temporary file {$tmpFile} not to have been deleted");

        $operation->processStop(new StopItem(true), $context);
        $this->assertFalse(file_exists($tmpFile), "Expecting temporary file {$tmpFile} to have been deleted");
    }
}
