<?php

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainMergeOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\CallbackTransformerOperation;
use Oliverde8\Component\PhpEtl\ExecutionContextFactory;
use Oliverde8\Component\PhpEtl\GenericChainFactory;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainMergeConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use PHPUnit\Framework\TestCase;

class ChainMergeOperationTest extends TestCase
{
    private ExecutionContext $context;
    private ChainBuilderV2 $chainBuilder;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->context = new ExecutionContext([], new LocalFileSystem());

        $executionFactory = new ExecutionContextFactory();
        $this->chainBuilder = new ChainBuilderV2(
            $executionFactory,
            [new GenericChainFactory(CallbackTransformerOperation::class, CallBackTransformerConfig::class)]
        );
    }

    public function testMergeTwoChains()
    {
        $chain1Config = new ChainConfig();
        $chain1Config->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new DataItem(['chain' => 1, 'data' => $item->getData()])));

        $chain2Config = new ChainConfig();
        $chain2Config->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new DataItem(['chain' => 2, 'data' => $item->getData()])));

        $mergeConfig = new ChainMergeConfig();
        $mergeConfig->addMerge($chain1Config)->addMerge($chain2Config);

        $operation = new ChainMergeOperation($this->chainBuilder, $mergeConfig);

        $item = new DataItem('test');
        $result = $operation->process($item, $this->context);

        $this->assertInstanceOf(MixItem::class, $result);
        $items = $result->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals(['chain' => 1, 'data' => 'test'], $items[0]->getData());
        $this->assertEquals(['chain' => 2, 'data' => 'test'], $items[1]->getData());
    }

    public function testMergeThreeChains()
    {
        $chain1Config = new ChainConfig();
        $chain1Config->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new DataItem('chain1')));

        $chain2Config = new ChainConfig();
        $chain2Config->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new DataItem('chain2')));

        $chain3Config = new ChainConfig();
        $chain3Config->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new DataItem('chain3')));

        $mergeConfig = new ChainMergeConfig();
        $mergeConfig->addMerge($chain1Config)->addMerge($chain2Config)->addMerge($chain3Config);

        $operation = new ChainMergeOperation($this->chainBuilder, $mergeConfig);

        $item = new DataItem('input');
        $result = $operation->process($item, $this->context);

        $items = $result->getItems();
        $this->assertCount(3, $items);
        $this->assertEquals('chain1', $items[0]->getData());
        $this->assertEquals('chain2', $items[1]->getData());
        $this->assertEquals('chain3', $items[2]->getData());
    }

    public function testMergeWithMultipleItemsPerChain()
    {
        $chain1Config = new ChainConfig();
        $chain1Config->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new MixItem([
            new DataItem('a1'),
            new DataItem('a2')
        ])));

        $chain2Config = new ChainConfig();
        $chain2Config->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new MixItem([
            new DataItem('b1'),
            new DataItem('b2')
        ])));

        $mergeConfig = new ChainMergeConfig();
        $mergeConfig->addMerge($chain1Config)->addMerge($chain2Config);

        $operation = new ChainMergeOperation($this->chainBuilder, $mergeConfig);

        $item = new DataItem('input');
        $result = $operation->process($item, $this->context);

        $items = $result->getItems();
        $this->assertCount(4, $items);
        $this->assertEquals('a1', $items[0]->getData());
        $this->assertEquals('a2', $items[1]->getData());
        $this->assertEquals('b1', $items[2]->getData());
        $this->assertEquals('b2', $items[3]->getData());
    }

    public function testMergePreservesOrder()
    {
        $results = [];

        $chain1Config = new ChainConfig();
        $chain1Config->addLink(new CallBackTransformerConfig(function(ItemInterface $item) use (&$results) {
            $results[] = 'chain1';
            return new DataItem('data1');
        }));

        $chain2Config = new ChainConfig();
        $chain2Config->addLink(new CallBackTransformerConfig(function(ItemInterface $item) use (&$results) {
            $results[] = 'chain2';
            return new DataItem('data2');
        }));

        $chain3Config = new ChainConfig();
        $chain3Config->addLink(new CallBackTransformerConfig(function(ItemInterface $item) use (&$results) {
            $results[] = 'chain3';
            return new DataItem('data3');
        }));

        $mergeConfig = new ChainMergeConfig();
        $mergeConfig->addMerge($chain1Config)->addMerge($chain2Config)->addMerge($chain3Config);

        $operation = new ChainMergeOperation($this->chainBuilder, $mergeConfig);

        $item = new DataItem('input');
        $operation->process($item, $this->context);

        $this->assertEquals(['chain1', 'chain2', 'chain3'], $results);
    }

    public function testProcessStopCallsAllChains()
    {
        $chain1Config = new ChainConfig();
        $chain1Config->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new DataItem('chain1')));

        $chain2Config = new ChainConfig();
        $chain2Config->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new DataItem('chain2')));

        $mergeConfig = new ChainMergeConfig();
        $mergeConfig->addMerge($chain1Config)->addMerge($chain2Config);

        $operation = new ChainMergeOperation($this->chainBuilder, $mergeConfig);

        $stopItem = new StopItem();
        $result = $operation->processStop($stopItem, $this->context);

        $this->assertSame($stopItem, $result);
        $this->assertCount(2, $operation->getChainProcessors());
    }

    public function testMergeWithEmptyChain()
    {
        $chain1Config = new ChainConfig();
        $chain1Config->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new DataItem('result')));

        $chain2Config = new ChainConfig();

        $mergeConfig = new ChainMergeConfig();
        $mergeConfig->addMerge($chain1Config)->addMerge($chain2Config);

        $operation = new ChainMergeOperation($this->chainBuilder, $mergeConfig);

        $item = new DataItem('input');
        $result = $operation->process($item, $this->context);

        $items = $result->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('result', $items[0]->getData());
        $this->assertEquals('input', $items[1]->getData());
    }

    public function testGetChainProcessors()
    {
        $chain1Config = new ChainConfig();
        $chain2Config = new ChainConfig();

        $mergeConfig = new ChainMergeConfig();
        $mergeConfig->addMerge($chain1Config)->addMerge($chain2Config);

        $operation = new ChainMergeOperation($this->chainBuilder, $mergeConfig);

        $processors = $operation->getChainProcessors();
        $this->assertCount(2, $processors);
        $this->assertContainsOnlyInstancesOf(\Oliverde8\Component\PhpEtl\ChainProcessorInterface::class, $processors);
    }

    public function testMergeWithDifferentDataTypes()
    {
        $chain1Config = new ChainConfig();
        $chain1Config->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new DataItem(['type' => 'array', 'value' => 1])));

        $chain2Config = new ChainConfig();
        $chain2Config->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new DataItem('string_value')));

        $chain3Config = new ChainConfig();
        $chain3Config->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new DataItem(123)));

        $mergeConfig = new ChainMergeConfig();
        $mergeConfig->addMerge($chain1Config)->addMerge($chain2Config)->addMerge($chain3Config);

        $operation = new ChainMergeOperation($this->chainBuilder, $mergeConfig);

        $item = new DataItem('input');
        $result = $operation->process($item, $this->context);

        $items = $result->getItems();
        $this->assertCount(3, $items);
        $this->assertEquals(['type' => 'array', 'value' => 1], $items[0]->getData());
        $this->assertEquals('string_value', $items[1]->getData());
        $this->assertEquals(123, $items[2]->getData());
    }

    public function testMergeWithChainTransformations()
    {
        $chain1Config = new ChainConfig();
        $chain1Config->addLink(new CallBackTransformerConfig(function(ItemInterface $item) {
            $data = $item->getData();
            return new DataItem($data * 2);
        }));

        $chain2Config = new ChainConfig();
        $chain2Config->addLink(new CallBackTransformerConfig(function(ItemInterface $item) {
            $data = $item->getData();
            return new DataItem($data * 3);
        }));

        $mergeConfig = new ChainMergeConfig();
        $mergeConfig->addMerge($chain1Config)->addMerge($chain2Config);

        $operation = new ChainMergeOperation($this->chainBuilder, $mergeConfig);

        $item = new DataItem(5);
        $result = $operation->process($item, $this->context);

        $items = $result->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals(10, $items[0]->getData());
        $this->assertEquals(15, $items[1]->getData());
    }

    public function testGetConfigurationClass()
    {
        $mergeConfig = new ChainMergeConfig();
        $mergeConfig->addMerge(new ChainConfig());

        $operation = new ChainMergeOperation($this->chainBuilder, $mergeConfig);

        $this->assertEquals(ChainMergeConfig::class, $operation->getConfigurationClass());
    }

    public function testMergeDoesNotStopOnFirstChain()
    {
        $processedChains = [];

        $chain1Config = new ChainConfig();
        $chain1Config->addLink(new CallBackTransformerConfig(function(ItemInterface $item) use (&$processedChains) {
            $processedChains[] = 1;
            return new DataItem('chain1');
        }));

        $chain2Config = new ChainConfig();
        $chain2Config->addLink(new CallBackTransformerConfig(function(ItemInterface $item) use (&$processedChains) {
            $processedChains[] = 2;
            return new DataItem('chain2');
        }));

        $chain3Config = new ChainConfig();
        $chain3Config->addLink(new CallBackTransformerConfig(function(ItemInterface $item) use (&$processedChains) {
            $processedChains[] = 3;
            return new DataItem('chain3');
        }));

        $mergeConfig = new ChainMergeConfig();
        $mergeConfig->addMerge($chain1Config)->addMerge($chain2Config)->addMerge($chain3Config);

        $operation = new ChainMergeOperation($this->chainBuilder, $mergeConfig);

        $item = new DataItem('input');
        $operation->process($item, $this->context);

        $this->assertEquals([1, 2, 3], $processedChains);
    }

    public function testMergeWithComplexChainOperations()
    {
        $chain1Config = new ChainConfig();
        $chain1Config->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new DataItem(strtoupper($item->getData()))));
        $chain1Config->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new DataItem($item->getData() . '_PROCESSED')));

        $chain2Config = new ChainConfig();
        $chain2Config->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new DataItem(strtolower($item->getData()))));
        $chain2Config->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new DataItem($item->getData() . '_processed')));

        $mergeConfig = new ChainMergeConfig();
        $mergeConfig->addMerge($chain1Config)->addMerge($chain2Config);

        $operation = new ChainMergeOperation($this->chainBuilder, $mergeConfig);

        $item = new DataItem('Test');
        $result = $operation->process($item, $this->context);

        $items = $result->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('TEST_PROCESSED', $items[0]->getData());
        $this->assertEquals('test_processed', $items[1]->getData());
    }
}

