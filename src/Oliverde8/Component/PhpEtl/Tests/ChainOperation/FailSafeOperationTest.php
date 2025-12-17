<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainOperation\ChainRepeatOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\FailSafeOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\CallbackTransformerOperation;
use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\Exception\ChainOperationException;
use Oliverde8\Component\PhpEtl\ExecutionContextFactory;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use PHPUnit\Framework\TestCase;

class FailSafeOperationTest extends TestCase
{
    public function testSingleFail()
    {
        $callNum = 0;
        $failOperation = new CallbackTransformerOperation(function (ItemInterface $item) use (&$callNum) {
            if ($callNum == 0 || $callNum == 2) {
                return new DataItem(['val' => $callNum++]);
            }
            if ($callNum == 1) {
                $callNum++;
                throw new \Exception("Exception at $callNum");
            }
        });
        $endOperation = new CallbackTransformerOperation(function (ItemInterface $item) use (&$results) {
            $results[] = $item->getData();
            return $item;
        });

        $chain = $this->createChain([$failOperation], [$endOperation]);
        $chain->process(new \ArrayIterator([['var' => 1],['var' => 2]]), []);

        $this->assertEquals([['val' => 0], ['val' => 2]], $results);
    }

    public function testToManyFail()
    {
        $callNum = 0;
        $failOperation = new CallbackTransformerOperation(function (ItemInterface $item) use (&$callNum): void {
            $callNum++;
            throw new \Exception("Exception at $callNum");
        });
        $results = [];
        $endOperation = new CallbackTransformerOperation(function (ItemInterface $item) use (&$results) {
            $results[] = $item->getData();
            return $item;
        });

        $chain = $this->createChain([$failOperation], [$endOperation]);
        $e = null;
        try {
            $chain->process(new \ArrayIterator([['var' => 1], ['var' => 2]]), []);
        } catch (\Exception $e) {}

        $this->assertInstanceOf(ChainOperationException::class, $e);
        $this->assertInstanceOf(\Exception::class, $e->getPrevious());
        $this->assertEquals("Exception at 2", $e->getPrevious()->getMessage());
        $this->assertEquals([], $results);
    }

    protected function createChain(array $failSafeOperation, array $afterOperations): ChainProcessor
    {
        $executionFactory = new ExecutionContextFactory();
        $repeatOperation = new FailSafeOperation(
            new ChainProcessor($failSafeOperation, $executionFactory),
            [\Exception::class],
            2,
        );

        array_unshift($afterOperations, $repeatOperation);
        return new ChainProcessor($afterOperations, $executionFactory);
    }
}