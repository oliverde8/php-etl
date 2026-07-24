<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\ChainOperation\Grouping\BatchOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\IfOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\CallbackTransformerOperation;
use Oliverde8\Component\PhpEtl\ExecutionContextFactory;
use Oliverde8\Component\PhpEtl\GenericChainFactory;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem;
use Oliverde8\Component\PhpEtl\OperationConfig\Grouping\BatchConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\IfConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use Oliverde8\Component\RuleEngine\RuleApplier;
use PHPUnit\Framework\TestCase;

class IfOperationTest extends TestCase
{
    private ExecutionContext $context;
    private ChainBuilderV2 $chainBuilder;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->context = new ExecutionContext([], new LocalFileSystem());
        $this->chainBuilder = new ChainBuilderV2(
            new ExecutionContextFactory(),
            [
                new GenericChainFactory(CallbackTransformerOperation::class, CallBackTransformerConfig::class),
                new GenericChainFactory(BatchOperation::class, BatchConfig::class),
            ]
        );
    }

    private function ruleApplier(bool $result): RuleApplier
    {
        $mock = $this->createMock(RuleApplier::class);
        $mock->method('apply')->willReturn($result);

        return $mock;
    }

    private function singleResult(ItemInterface $item): DataItem
    {
        $this->assertInstanceOf(MixItem::class, $item);
        $items = $item->getItems();
        $this->assertCount(1, $items);
        $this->assertInstanceOf(DataItem::class, $items[0]);

        return $items[0];
    }

    public function testConditionTrueRunsThenBranch(): void
    {
        $then = new ChainConfig();
        $then->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new DataItem(['branch' => 'then'])));

        $config = new IfConfig(rules: [true], then: $then);
        $operation = new IfOperation($this->chainBuilder, $this->ruleApplier(true), $config);

        $result = $operation->process(new DataItem(['branch' => 'input']), $this->context);

        $this->assertEquals(['branch' => 'then'], $this->singleResult($result)->getData());
    }

    public function testConditionFalseRunsElseBranch(): void
    {
        $then = new ChainConfig();
        $then->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new DataItem(['branch' => 'then'])));

        $else = new ChainConfig();
        $else->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new DataItem(['branch' => 'else'])));

        $config = new IfConfig(rules: [true], then: $then, else: $else);
        $operation = new IfOperation($this->chainBuilder, $this->ruleApplier(false), $config);

        $result = $operation->process(new DataItem(['branch' => 'input']), $this->context);

        $this->assertEquals(['branch' => 'else'], $this->singleResult($result)->getData());
    }

    public function testConditionFalseWithoutElsePassesThrough(): void
    {
        $then = new ChainConfig();
        $then->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new DataItem(['branch' => 'then'])));

        $config = new IfConfig(rules: [true], then: $then);
        $operation = new IfOperation($this->chainBuilder, $this->ruleApplier(false), $config);

        $item = new DataItem(['branch' => 'input']);
        $result = $operation->process($item, $this->context);

        $this->assertSame($item, $result);
    }

    public function testNegateFlipsCondition(): void
    {
        $then = new ChainConfig();
        $then->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new DataItem(['branch' => 'then'])));

        $config = new IfConfig(rules: [true], then: $then, negate: true);
        $operation = new IfOperation($this->chainBuilder, $this->ruleApplier(true), $config);

        $item = new DataItem(['branch' => 'input']);
        $result = $operation->process($item, $this->context);

        // Rule result is true, but negate flips it to false, and there is no else branch.
        $this->assertSame($item, $result);
    }

    public function testGetChainProcessorsWithoutElse(): void
    {
        $then = new ChainConfig();
        $config = new IfConfig(rules: [true], then: $then);
        $operation = new IfOperation($this->chainBuilder, $this->ruleApplier(true), $config);

        $this->assertCount(1, $operation->getChainProcessors());
    }

    public function testGetChainProcessorsWithElse(): void
    {
        $then = new ChainConfig();
        $else = new ChainConfig();
        $config = new IfConfig(rules: [true], then: $then, else: $else);
        $operation = new IfOperation($this->chainBuilder, $this->ruleApplier(true), $config);

        $this->assertCount(2, $operation->getChainProcessors());
    }

    public function testProcessStopDrainsBothBranches(): void
    {
        // BatchOperation only flushes its buffer on processStop, so a flushed value downstream is a reliable
        // signal that the StopItem actually reached that branch (a plain closure never sees StopItems, since
        // CallbackTransformerOperation only defines processData).
        $thenFlushed = null;
        $elseFlushed = null;

        $then = new ChainConfig();
        $then->addLink(new BatchConfig(size: 10))
            ->addLink(new CallBackTransformerConfig(function (ItemInterface $item) use (&$thenFlushed) {
                $thenFlushed = $item->getData();
                return $item;
            }));

        $else = new ChainConfig();
        $else->addLink(new BatchConfig(size: 10))
            ->addLink(new CallBackTransformerConfig(function (ItemInterface $item) use (&$elseFlushed) {
                $elseFlushed = $item->getData();
                return $item;
            }));

        $config = new IfConfig(rules: [true], then: $then, else: $else);

        $ruleApplier = $this->createMock(RuleApplier::class);
        $ruleApplier->method('apply')->willReturnOnConsecutiveCalls(true, false);

        $operation = new IfOperation($this->chainBuilder, $ruleApplier, $config);

        // Route one item into each branch so both BatchOperations have something buffered to flush.
        $operation->process(new DataItem(['id' => 'then-item']), $this->context);
        $operation->process(new DataItem(['id' => 'else-item']), $this->context);

        $this->assertNull($thenFlushed);
        $this->assertNull($elseFlushed);

        $stopItem = new StopItem();
        $result = $operation->processStop($stopItem, $this->context);

        $this->assertSame($stopItem, $result);
        $this->assertEquals([['id' => 'then-item']], $thenFlushed);
        $this->assertEquals([['id' => 'else-item']], $elseFlushed);
    }

    public function testIsolateContextDoesNotLeakToParent(): void
    {
        $then = new ChainConfig();
        $then->addLink(new CallBackTransformerConfig(function (ItemInterface $item, ExecutionContext $context) {
            $context->setParameter('touched', true);
            return $item;
        }));

        $config = new IfConfig(rules: [true], then: $then, isolateContext: true);
        $operation = new IfOperation($this->chainBuilder, $this->ruleApplier(true), $config);

        $operation->process(new DataItem([]), $this->context);

        $this->assertNull($this->context->getParameter('touched'));
    }

    public function testWithoutIsolationLeaksToParent(): void
    {
        $then = new ChainConfig();
        $then->addLink(new CallBackTransformerConfig(function (ItemInterface $item, ExecutionContext $context) {
            $context->setParameter('touched', true);
            return $item;
        }));

        $config = new IfConfig(rules: [true], then: $then);
        $operation = new IfOperation($this->chainBuilder, $this->ruleApplier(true), $config);

        $operation->process(new DataItem([]), $this->context);

        $this->assertTrue($this->context->getParameter('touched'));
    }

    public function testEmptyRulesThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new IfConfig(rules: [], then: new ChainConfig());
    }
}
