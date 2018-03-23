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
use Oliverde8\Component\PhpEtl\Model\File\FileWriterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FileWriterOperationTest extends TestCase
{
    /** @var MockObject */
    protected $writerMock;

    /** @var FileWriterOperation */
    protected $writerOperation;

    protected function setUp()/* The :void return type declaration that should be here would cause a BC issue */
    {
        parent::setUp();

        $this->writerMock = $this->getMockBuilder(FileWriterInterface::class)
            ->getMock();

        $this->writerOperation = new FileWriterOperation($this->writerMock);
    }

    public function testWrite()
    {
        $this->writerMock
            ->expects($this->exactly(1))
            ->method('write')
            ->with(['test']);

        $data = [];
        $this->writerOperation->process(new DataItem(['test']), $data);
    }
}
