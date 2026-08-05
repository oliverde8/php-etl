<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\ChainOperation\Grouping\BatchOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\SwitchOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\CallbackTransformerOperation;
use Oliverde8\Component\PhpEtl\Expression\Expression;
use Oliverde8\Component\PhpEtl\ExecutionContextFactory;
use Oliverde8\Component\PhpEtl\GenericChainFactory;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\MixItem;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem;
use Oliverde8\Component\PhpEtl\OperationConfig\Grouping\BatchConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\SwitchConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use Oliverde8\Component\RuleEngine\RuleApplier;
use Oliverde8\Component\RuleEngine\RuleConfig\GetRuleConfig;
use PHPUnit\Framework\TestCase;

class SwitchOperationTest extends TestCase
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

    private function branch(string $tag): ChainConfig
    {
        $chain = new ChainConfig();
        $chain->addLink(new CallBackTransformerConfig(fn(ItemInterface $item) => new DataItem(['branch' => $tag])));
        return $chain;
    }

    private function singleResult(ItemInterface $item): DataItem
    {
        $this->assertInstanceOf(MixItem::class, $item);
        $items = $item->getItems();
        $this->assertCount(1, $items);
        $this->assertInstanceOf(DataItem::class, $items[0]);

        return $items[0];
    }

    public function testFirstMatchingCaseWins(): void
    {
        $config = (new SwitchConfig())
            ->addCase($this->branch('us'), new Expression('data["country"] == "US"'))
            ->addCase($this->branch('fr'), new Expression('data["country"] == "FR"'));

        $operation = new SwitchOperation($this->chainBuilder, $this->createMock(RuleApplier::class), $config);

        $result = $operation->process(new DataItem(['country' => 'FR']), $this->context);

        $this->assertEquals(['branch' => 'fr'], $this->singleResult($result)->getData());
    }

    public function testEarlierCaseWinsOverLaterMatchingCase(): void
    {
        $config = (new SwitchConfig())
            ->addCase($this->branch('first'), new Expression('true'))
            ->addCase($this->branch('second'), new Expression('true'));

        $operation = new SwitchOperation($this->chainBuilder, $this->createMock(RuleApplier::class), $config);

        $result = $operation->process(new DataItem([]), $this->context);

        $this->assertEquals(['branch' => 'first'], $this->singleResult($result)->getData());
    }

    public function testFallsBackToDefaultWhenNoCaseMatches(): void
    {
        $config = (new SwitchConfig(default: $this->branch('default')))
            ->addCase($this->branch('us'), new Expression('data["country"] == "US"'));

        $operation = new SwitchOperation($this->chainBuilder, $this->createMock(RuleApplier::class), $config);

        $result = $operation->process(new DataItem(['country' => 'DE']), $this->context);

        $this->assertEquals(['branch' => 'default'], $this->singleResult($result)->getData());
    }

    public function testNoMatchWithoutDefaultPassesThrough(): void
    {
        $config = (new SwitchConfig())
            ->addCase($this->branch('us'), new Expression('data["country"] == "US"'));

        $operation = new SwitchOperation($this->chainBuilder, $this->createMock(RuleApplier::class), $config);

        $item = new DataItem(['country' => 'DE']);
        $result = $operation->process($item, $this->context);

        $this->assertSame($item, $result);
    }

    public function testCaseUsingRules(): void
    {
        $ruleApplier = $this->createMock(RuleApplier::class);
        $ruleApplier->method('apply')->willReturn(true);

        $config = (new SwitchConfig())->addCase($this->branch('matched'), [true]);

        $operation = new SwitchOperation($this->chainBuilder, $ruleApplier, $config);
        $result = $operation->process(new DataItem([]), $this->context);

        $this->assertEquals(['branch' => 'matched'], $this->singleResult($result)->getData());
    }

    public function testCaseUsingRuleConfig(): void
    {
        $ruleApplier = $this->createMock(RuleApplier::class);
        $ruleApplier->method('applyConfig')->willReturn(true);

        $config = (new SwitchConfig())->addCase($this->branch('matched'), new GetRuleConfig('country'));

        $operation = new SwitchOperation($this->chainBuilder, $ruleApplier, $config);
        $result = $operation->process(new DataItem(['country' => 'US']), $this->context);

        $this->assertEquals(['branch' => 'matched'], $this->singleResult($result)->getData());
    }

    public function testCaseCanReadContext(): void
    {
        $config = (new SwitchConfig())->addCase($this->branch('matched'), new Expression('context["country"] == "US"'));

        $operation = new SwitchOperation($this->chainBuilder, $this->createMock(RuleApplier::class), $config);

        $context = new ExecutionContext(['country' => 'US'], new LocalFileSystem());
        $result = $operation->process(new DataItem([]), $context);

        $this->assertEquals(['branch' => 'matched'], $this->singleResult($result)->getData());
    }

    public function testGetChainProcessorsIncludesCasesAndDefault(): void
    {
        $config = (new SwitchConfig(default: new ChainConfig()))
            ->addCase(new ChainConfig(), new Expression('true'))
            ->addCase(new ChainConfig(), new Expression('true'));

        $operation = new SwitchOperation($this->chainBuilder, $this->createMock(RuleApplier::class), $config);

        $this->assertCount(3, $operation->getChainProcessors());
    }

    public function testGetChainProcessorsWithoutDefault(): void
    {
        $config = (new SwitchConfig())->addCase(new ChainConfig(), new Expression('true'));

        $operation = new SwitchOperation($this->chainBuilder, $this->createMock(RuleApplier::class), $config);

        $this->assertCount(1, $operation->getChainProcessors());
    }

    public function testProcessStopDrainsAllBranches(): void
    {
        $usFlushed = null;
        $defaultFlushed = null;

        $us = new ChainConfig();
        $us->addLink(new BatchConfig(size: 10))
            ->addLink(new CallBackTransformerConfig(function (ItemInterface $item) use (&$usFlushed) {
                $usFlushed = $item->getData();
                return $item;
            }));

        $default = new ChainConfig();
        $default->addLink(new BatchConfig(size: 10))
            ->addLink(new CallBackTransformerConfig(function (ItemInterface $item) use (&$defaultFlushed) {
                $defaultFlushed = $item->getData();
                return $item;
            }));

        $config = (new SwitchConfig(default: $default))
            ->addCase($us, new Expression('data["country"] == "US"'));

        $operation = new SwitchOperation($this->chainBuilder, $this->createMock(RuleApplier::class), $config);

        $operation->process(new DataItem(['id' => 'us-item', 'country' => 'US']), $this->context);
        $operation->process(new DataItem(['id' => 'default-item', 'country' => 'DE']), $this->context);

        $this->assertNull($usFlushed);
        $this->assertNull($defaultFlushed);

        $stopItem = new StopItem();
        $result = $operation->processStop($stopItem, $this->context);

        $this->assertSame($stopItem, $result);
        $this->assertEquals([['id' => 'us-item', 'country' => 'US']], $usFlushed);
        $this->assertEquals([['id' => 'default-item', 'country' => 'DE']], $defaultFlushed);
    }

    public function testIsolateContextDoesNotLeakToParent(): void
    {
        $branch = new ChainConfig();
        $branch->addLink(new CallBackTransformerConfig(function (ItemInterface $item, ExecutionContext $context) {
            $context->setParameter('touched', true);
            return $item;
        }));

        $config = (new SwitchConfig(isolateContext: true))->addCase($branch, new Expression('true'));
        $operation = new SwitchOperation($this->chainBuilder, $this->createMock(RuleApplier::class), $config);

        $operation->process(new DataItem([]), $this->context);

        $this->assertNull($this->context->getParameter('touched'));
    }

    public function testWithoutIsolationLeaksToParent(): void
    {
        $branch = new ChainConfig();
        $branch->addLink(new CallBackTransformerConfig(function (ItemInterface $item, ExecutionContext $context) {
            $context->setParameter('touched', true);
            return $item;
        }));

        $config = (new SwitchConfig())->addCase($branch, new Expression('true'));
        $operation = new SwitchOperation($this->chainBuilder, $this->createMock(RuleApplier::class), $config);

        $operation->process(new DataItem([]), $this->context);

        $this->assertTrue($this->context->getParameter('touched'));
    }

    public function testGetConfigurationClass(): void
    {
        $config = (new SwitchConfig())->addCase(new ChainConfig(), new Expression('true'));
        $operation = new SwitchOperation($this->chainBuilder, $this->createMock(RuleApplier::class), $config);

        $this->assertEquals(SwitchConfig::class, $operation->getConfigurationClass());
    }
}
