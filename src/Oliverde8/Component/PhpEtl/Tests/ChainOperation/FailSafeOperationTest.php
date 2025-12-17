<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\ChainOperation\FailSafeOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\CallbackTransformerOperation;
use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\Exception\ChainOperationException;
use Oliverde8\Component\PhpEtl\ExecutionContextFactory;
use Oliverde8\Component\PhpEtl\GenericChainFactory;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\FailSafeConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use PHPUnit\Framework\TestCase;

class FailSafeOperationTest extends TestCase
{
    public function testSingleFail()
    {
        $callNum = 0;
        $failOperation = new CallbackTransformerOperation(new CallBackTransformerConfig(function (ItemInterface $item) use (&$callNum) {
            if ($callNum == 0 || $callNum == 2) {
                return new DataItem(['val' => $callNum++]);
            }
            if ($callNum == 1) {
                $callNum++;
                throw new \Exception("Exception at $callNum");
            }
            return $item;
        }));
        $endOperation = new CallbackTransformerOperation(new CallBackTransformerConfig(function (ItemInterface $item) use (&$results) {
            $results[] = $item->getData();
            return $item;
        }));

        $chain = $this->createChain([$failOperation], [$endOperation]);
        $chain->process(new \ArrayIterator([['var' => 1],['var' => 2]]), []);

        $this->assertEquals([['val' => 0], ['val' => 2]], $results);
    }

    public function testToManyFail()
    {
        $callNum = 0;
        $failOperation = new CallbackTransformerOperation(new CallBackTransformerConfig(function (ItemInterface $item) use (&$callNum) {
            $callNum++;
            throw new \Exception("Exception at $callNum");
        }));
        $results = [];
        $endOperation = new CallbackTransformerOperation(new CallBackTransformerConfig(function (ItemInterface $item) use (&$results) {
            $results[] = $item->getData();
            return $item;
        }));

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

        $failSafeChainConfig = new ChainConfig();

        $reflection = new \ReflectionClass(CallbackTransformerOperation::class);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);

        foreach ($failSafeOperation as $operation) {
            if ($operation instanceof CallbackTransformerOperation) {
                $config = $configProperty->getValue($operation);
                $failSafeChainConfig->addLink($config);
            }
        }

        $chainBuilder = new ChainBuilderV2(
            $executionFactory,
            [new GenericChainFactory(CallbackTransformerOperation::class, CallBackTransformerConfig::class)]
        );

        $repeatOperation = new FailSafeOperation(
            $chainBuilder,
            new FailSafeConfig($failSafeChainConfig, [\Exception::class], 2)
        );

        array_unshift($afterOperations, $repeatOperation);
        return new ChainProcessor($afterOperations, $executionFactory);
    }
}