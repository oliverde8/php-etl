<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainOperation\ChainRepeatOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\CallbackTransformerOperation;
use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\ExecutionContextFactory;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\GroupedItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Tests\Item\TestAsyncItem;
use PHPUnit\Framework\TestCase;

class ChainRepeatOperationTest extends TestCase
{
    public function testRepeatDataItem()
    {
        $callNum = 0;
        $results = [];
        $repeatedOperation = new CallbackTransformerOperation(function (ItemInterface $item) use (&$callNum) {
            return new DataItem(['val' => $callNum++]);
        });
        $endOperation = new CallbackTransformerOperation(function (ItemInterface $item) use (&$results) {
            $results[] = $item->getData();
            return $item;
        });

        $chain = $this->createChain([$repeatedOperation], [$endOperation], 'data["val"] != 3');
        $chain->process(new \ArrayIterator([['var' => 1]]), []);

        $this->assertEquals([['val' => 0], ['val' => 1], ['val' => 2]], $results);
    }

    public function testRepeatGroupedItem()
    {
        $callNum = 0;
        $results = [];
        $repeatedOperation = new CallbackTransformerOperation(function (ItemInterface $item) use (&$callNum) {
            return new GroupedItem(new \ArrayIterator([['val' => $callNum++], ['val' => $callNum++]]));
        });
        $endOperation = new CallbackTransformerOperation(function (ItemInterface $item) use (&$results) {
            if ($item instanceof DataItem) {
                $results[] = $item->getData();
            }
            return $item;
        });

        $chain = $this->createChain([$repeatedOperation], [$endOperation], 'data["val"] != 3');
        $chain->process(new \ArrayIterator([['var' => 1]]), []);
        $this->assertEquals([['val' => 0], ['val' => 1], ['val' => 2]], $results);
    }

    public function testAsyncDisabled()
    {
        $results = [];
        $callNum = 0;
        $repeatedOperation = new CallbackTransformerOperation(function (ItemInterface $item) use (&$callNum) {
            return new MixItem([
                new TestAsyncItem(new DataItem(['val' => $callNum++, 'speed' => 'slow']), 2),
                new TestAsyncItem(new DataItem(['val' => $callNum++, 'speed' => 'fast']), 1),
            ]);
        });
        $endOperation = new CallbackTransformerOperation(function (ItemInterface $item) use (&$results) {
            $results[] = $item->getData();
            return $item;
        });

        $chain = $this->createChain([$repeatedOperation], [$endOperation], 'data["val"] != 3');
        $chain->process(new \ArrayIterator([['var' => 1]]), []);

        // Fast items will need to wait the slow items as we do not allow asynchronous execution inside this repeat.
        $this->assertEquals([['val' => 0, 'speed' => 'slow'], ['val' => 1, 'speed' => 'fast'], ['val' => 2, 'speed' => 'slow']], $results);
    }

    public function testAsyncEnabled()
    {
        $results = [];
        $callNum = 0;
        $repeatedOperation = new CallbackTransformerOperation(function (ItemInterface $item) use (&$callNum) {
            return new MixItem([
                new TestAsyncItem(new DataItem(['val' => $callNum++, 'speed' => 'slow']), 2),
                new TestAsyncItem(new DataItem(['val' => $callNum++, 'speed' => 'fast']), 1),
            ]);
        });
        $endOperation = new CallbackTransformerOperation(function (ItemInterface $item) use (&$results) {
            $results[] = $item->getData();
            return $item;
        });

        $chain = $this->createChain([$repeatedOperation], [$endOperation], 'data["val"] != 5', true);
        $chain->process(new \ArrayIterator([['var' => 1]]), []);

        // Fast items will need to wait the slow items as we do not allow asynchronous execution inside this repeat.
        $this->assertEquals([
            ['val' => 1, 'speed' => 'fast'],
            ['val' => 0, 'speed' => 'slow'],
            ['val' => 3, 'speed' => 'fast'],
            ['val' => 2, 'speed' => 'slow'],
            ['val' => 4, 'speed' => 'slow'], // The second fast is not returned as our condition blocks it.
        ], $results);
    }

    protected function createChain(array $repeatedOperations, array $afterOperations, string $expression, bool $allowAsync = false): ChainProcessor
    {
        $executionFactory = new ExecutionContextFactory();
        $repeatOperation = new ChainRepeatOperation(
            new ChainProcessor($repeatedOperations, $executionFactory),
            $expression,
            $allowAsync,
        );

        array_unshift($afterOperations, $repeatOperation);
        return new ChainProcessor($afterOperations, $executionFactory);
    }
}