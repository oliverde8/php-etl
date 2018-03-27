<?php
/**
 * File ChainProcessorTest.php
 *
 * @author    de Cramer Oliver<oldec@smile.fr>
 * @copyright 2018 Smile
 */

namespace Oliverde8\Component\PhpEtl\Tests;

use Oliverde8\Component\PhpEtl\ChainOperation\Grouping\SimpleGroupingOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\CallbackTransformerOperation;
use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\Exception\ChainOperationException;
use Oliverde8\Component\PhpEtl\Item\ChainBreakItem;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use PHPUnit\Framework\TestCase;

class ChainProcessorTest extends TestCase
{

    public function testSimpleChain()
    {
        $count1 = 0;
        $count2 = 0;

        $mock1 = new CallbackTransformerOperation(function (ItemInterface $item) use (&$count1) {
            $count1++;

            return $item;
        });
        $mock2 = new CallbackTransformerOperation(function (ItemInterface $item) use (&$count2) {
            $count2++;

            return $item;
        });

        $chainProcessor = new ChainProcessor([$mock1, $mock2]);
        $chainProcessor->process(new \ArrayIterator([1,2]), ['toto']);

        $this->assertEquals(2, $count1);
        $this->assertEquals(2, $count2);
    }

    public function testChainBreak()
    {
        $count1 = 0;
        $count2 = 0;

        $mock1 = new CallbackTransformerOperation(function (ItemInterface $item) use (&$count1) {
            $count1++;

            return new ChainBreakItem();
        });
        $mock2 = new CallbackTransformerOperation(function (ItemInterface $item) use (&$count2) {
            $count2++;

            return $item;
        });

        $chainProcessor = new ChainProcessor([$mock1, $mock2]);
        $chainProcessor->process(new \ArrayIterator([1,2]), ['toto']);

        $this->assertEquals(2, $count1);
        $this->assertEquals(0, $count2);
    }

    public function testSingleGrouping()
    {
        $count1 = 0;
        $count2 = 0;

        $mock1 = new CallbackTransformerOperation(function (ItemInterface $item) use (&$count1) {
            $count1++;

            return $item;
        });
        $mock2 = new CallbackTransformerOperation(function (ItemInterface $item) use (&$count2) {
            $count2++;

            $data = $item->getData();
            $this->assertEquals(2, count($data));

            if ($count2 == 1) {
                $this->assertEquals('test01', $data[200]['value']);
                $this->assertEquals('test02', $data[201]['value']);
            }
            else if ($count2 == 2) {
                $this->assertEquals('test11', $data[200]['value']);
                $this->assertEquals('test12', $data[201]['value']);
            }

            return $item;
        });

        $data = [
            ['group' => 100, 'id' => 200, 'value' => 'test01'],
            ['group' => 100, 'id' => 201, 'value' => 'test02'],
            ['group' => 101, 'id' => 200, 'value' => 'test11'],
            ['group' => 101, 'id' => 201, 'value' => 'test12'],
        ];

        $chainProcessor = new ChainProcessor([$mock1, new SimpleGroupingOperation(['group'], ['id']), $mock2]);
        $chainProcessor->process(new \ArrayIterator($data), ['toto']);

        $this->assertEquals(4, $count1);
        $this->assertEquals(2, $count2);
    }

    public function testDoubleGrouping()
    {
        $count1 = 0;
        $count2 = 0;
        $count3 = 0;

        $mock1 = new CallbackTransformerOperation(function (ItemInterface $item) use (&$count1) {
            $count1++;

            return $item;
        });
        $mock2 = new CallbackTransformerOperation(function (ItemInterface $item) use (&$count2) {
            $count2++;

            $data = ['value' => 0];

            return new DataItem($data);
        });
        $mock3 = new CallbackTransformerOperation(function (ItemInterface $item) use (&$count3) {
            $count3++;

            return $item;
        });

        $data = [
            ['group' => 100, 'id' => 200, 'value' => 'test01'],
            ['group' => 100, 'id' => 201, 'value' => 'test02'],
            ['group' => 101, 'id' => 200, 'value' => 'test11'],
            ['group' => 101, 'id' => 201, 'value' => 'test12'],
        ];

        $chainProcessor = new ChainProcessor(
            [
                $mock1,
                new SimpleGroupingOperation(['group'], ['id']),
                $mock2,
                new SimpleGroupingOperation(['value']),
                $mock3,
            ]
        );
        $chainProcessor->process(new \ArrayIterator($data), ['toto']);

        $this->assertEquals(4, $count1);
        $this->assertEquals(2, $count2);
        $this->assertEquals(1, $count3);
    }

    public function testException()
    {
        $mock1 = new CallbackTransformerOperation(function (ItemInterface $item) use (&$count1) {
            throw new \Exception('Test exception');
        });

        try {
            $chainProcessor = new ChainProcessor(["op1" => $mock1]);
            $chainProcessor->process(new \ArrayIterator(['test']), ['toto']);
        } catch (ChainOperationException $exception) {
            $this->assertEquals('op1', $exception->getChainOperationName());
            $this->assertContains('1', $exception->getMessage());
        }
    }


}
