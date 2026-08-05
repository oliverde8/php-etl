<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation\Transformer;

use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\FilterDataOperation;
use Oliverde8\Component\PhpEtl\Expression\Expression;
use Oliverde8\Component\PhpEtl\Item\ChainBreakItem;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\RuleEngine\RuleApplier;
use Oliverde8\Component\RuleEngine\RuleConfig\GetRuleConfig;
use PHPUnit\Framework\TestCase;

class FilterDataOperationTest extends TestCase
{
    private function ruleApplier(bool $result): RuleApplier
    {
        $mock = $this->createMock(RuleApplier::class);
        $mock->method('apply')->willReturn($result);

        return $mock;
    }

    public function testRulesConditionTruePassesItemThrough(): void
    {
        $operation = new FilterDataOperation($this->ruleApplier(true), new FilterDataConfig(rules: [true]));

        $item = new DataItem(['subscribed' => true]);
        $result = $operation->process($item, new ExecutionContext([], new LocalFileSystem()));

        $this->assertSame($item, $result);
    }

    public function testRulesConditionFalseBreaksChain(): void
    {
        $operation = new FilterDataOperation($this->ruleApplier(false), new FilterDataConfig(rules: [true]));

        $result = $operation->process(new DataItem(['subscribed' => false]), new ExecutionContext([], new LocalFileSystem()));

        $this->assertInstanceOf(ChainBreakItem::class, $result);
    }

    public function testNegateFlipsRulesCondition(): void
    {
        $operation = new FilterDataOperation($this->ruleApplier(true), new FilterDataConfig(rules: [true], negate: true));

        $result = $operation->process(new DataItem(['subscribed' => true]), new ExecutionContext([], new LocalFileSystem()));

        $this->assertInstanceOf(ChainBreakItem::class, $result);
    }

    public function testExpressionConditionTruePassesItemThrough(): void
    {
        $config = new FilterDataConfig(new Expression('data["subscribed"] == true'));
        $operation = new FilterDataOperation($this->createMock(RuleApplier::class), $config);

        $item = new DataItem(['subscribed' => true]);
        $result = $operation->process($item, new ExecutionContext([], new LocalFileSystem()));

        $this->assertSame($item, $result);
    }

    public function testExpressionConditionFalseBreaksChain(): void
    {
        $config = new FilterDataConfig(new Expression('data["subscribed"] == true'));
        $operation = new FilterDataOperation($this->createMock(RuleApplier::class), $config);

        $result = $operation->process(new DataItem(['subscribed' => false]), new ExecutionContext([], new LocalFileSystem()));

        $this->assertInstanceOf(ChainBreakItem::class, $result);
    }

    public function testExpressionConditionCanReadContext(): void
    {
        $config = new FilterDataConfig(new Expression('context["country"] == "US"'));
        $operation = new FilterDataOperation($this->createMock(RuleApplier::class), $config);

        $context = new ExecutionContext(['country' => 'US'], new LocalFileSystem());
        $item = new DataItem([]);
        $result = $operation->process($item, $context);

        $this->assertSame($item, $result);
    }

    public function testNegateFlipsExpressionCondition(): void
    {
        $config = new FilterDataConfig(new Expression('data["subscribed"] == true'), negate: true);
        $operation = new FilterDataOperation($this->createMock(RuleApplier::class), $config);

        $result = $operation->process(new DataItem(['subscribed' => true]), new ExecutionContext([], new LocalFileSystem()));

        $this->assertInstanceOf(ChainBreakItem::class, $result);
    }

    public function testRuleConfigConditionTruePassesItemThrough(): void
    {
        $ruleApplier = $this->createMock(RuleApplier::class);
        $ruleApplier->method('applyConfig')->willReturn(true);

        $config = new FilterDataConfig(new GetRuleConfig('subscribed'));
        $operation = new FilterDataOperation($ruleApplier, $config);

        $item = new DataItem(['subscribed' => true]);
        $result = $operation->process($item, new ExecutionContext([], new LocalFileSystem()));

        $this->assertSame($item, $result);
    }

    public function testRuleConfigConditionFalseBreaksChain(): void
    {
        $ruleApplier = $this->createMock(RuleApplier::class);
        $ruleApplier->method('applyConfig')->willReturn(false);

        $config = new FilterDataConfig(new GetRuleConfig('subscribed'));
        $operation = new FilterDataOperation($ruleApplier, $config);

        $result = $operation->process(new DataItem(['subscribed' => false]), new ExecutionContext([], new LocalFileSystem()));

        $this->assertInstanceOf(ChainBreakItem::class, $result);
    }

    public function testMissingRulesThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new FilterDataConfig();
    }
}
