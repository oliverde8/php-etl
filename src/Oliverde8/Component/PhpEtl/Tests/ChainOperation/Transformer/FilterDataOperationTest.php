<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation\Transformer;

use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\FilterDataOperation;
use Oliverde8\Component\PhpEtl\Item\ChainBreakItem;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\FilterDataConfig;
use Oliverde8\Component\RuleEngine\RuleApplier;
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
        $config = new FilterDataConfig(expression: 'data["subscribed"] == true');
        $operation = new FilterDataOperation($this->createMock(RuleApplier::class), $config);

        $item = new DataItem(['subscribed' => true]);
        $result = $operation->process($item, new ExecutionContext([], new LocalFileSystem()));

        $this->assertSame($item, $result);
    }

    public function testExpressionConditionFalseBreaksChain(): void
    {
        $config = new FilterDataConfig(expression: 'data["subscribed"] == true');
        $operation = new FilterDataOperation($this->createMock(RuleApplier::class), $config);

        $result = $operation->process(new DataItem(['subscribed' => false]), new ExecutionContext([], new LocalFileSystem()));

        $this->assertInstanceOf(ChainBreakItem::class, $result);
    }

    public function testExpressionConditionCanReadContext(): void
    {
        $config = new FilterDataConfig(expression: 'context["country"] == "US"');
        $operation = new FilterDataOperation($this->createMock(RuleApplier::class), $config);

        $context = new ExecutionContext(['country' => 'US'], new LocalFileSystem());
        $item = new DataItem([]);
        $result = $operation->process($item, $context);

        $this->assertSame($item, $result);
    }

    public function testNegateFlipsExpressionCondition(): void
    {
        $config = new FilterDataConfig(expression: 'data["subscribed"] == true', negate: true);
        $operation = new FilterDataOperation($this->createMock(RuleApplier::class), $config);

        $result = $operation->process(new DataItem(['subscribed' => true]), new ExecutionContext([], new LocalFileSystem()));

        $this->assertInstanceOf(ChainBreakItem::class, $result);
    }

    public function testMissingRulesAndExpressionThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new FilterDataConfig();
    }

    public function testRulesAndExpressionAreMutuallyExclusive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new FilterDataConfig(rules: [true], expression: 'data["a"] == true');
    }
}
