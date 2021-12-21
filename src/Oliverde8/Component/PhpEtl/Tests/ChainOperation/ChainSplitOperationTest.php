<?php
/**
 * File ChainSplitOperationTest.php
 *
 * @author    de Cramer Oliver<oldec@smile.fr>
 * @copyright 2018 Smile
 */

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainOperation\ChainSplitOperation;
use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\ChainProcessorInterface;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use PHPUnit\Framework\TestCase;

class ChainSplitOperationTest extends TestCase
{
    public function testDataProcessing()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject[] $processors */
        $processors = [
            $this->getMockBuilder(ChainProcessorInterface::class)->getMock(),
            $this->getMockBuilder(ChainProcessorInterface::class)->getMock(),
        ];
        $context = $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()->getMock();

        $datas = [
            new DataItem(['test-1']),
            new DataItem(['test-2']),
        ];

        foreach ($processors as $i => $processor) {
            $processor->expects($this->exactly(2))
                ->method('processItem')
                ->withConsecutive([$datas[0], 0, $context], [$datas[1], 0, $context]);
        }


        $splitOperation = new ChainSplitOperation($processors);
        $splitOperation->process($datas[0], $context);
        $splitOperation->process($datas[1], $context);
    }

    public function testStopProcess()
    {
        $stopItem = new StopItem();

        /** @var \PHPUnit_Framework_MockObject_MockObject[] $processors */
        $processors = [
            $this->getMockBuilder(ChainProcessorInterface::class)->getMock(),
            $this->getMockBuilder(ChainProcessorInterface::class)->getMock(),
        ];
        $context = $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()->getMock();
        $datas = [
            new DataItem(['test-1']),
            new DataItem(['test-2']),
        ];

        $processors[0]
            ->expects($this->exactly(3))
            ->method('processItem')
            ->withConsecutive([$datas[0], 0, $context], [$stopItem, 0, $context], [$stopItem, 0, $context])
            ->willReturnOnConsecutiveCalls($datas[0], $datas[1], $stopItem);

        $processors[1]
            ->expects($this->exactly(3))
            ->method('processItem')
            ->withConsecutive([$datas[0], 0, $context], [$stopItem, 0, $context], [$stopItem, 0, $context])
            ->willReturnOnConsecutiveCalls($datas[0], $stopItem, $stopItem);

        $splitOperation = new ChainSplitOperation($processors);
        $splitOperation->process($datas[0], $context);

        while ($splitOperation->process($stopItem, $context) !== $stopItem);
    }
}
