<?php

namespace Oliverde8\Component\RuleEngine\Tests;

use Oliverde8\Component\RuleEngine\Exceptions\RuleException;
use Oliverde8\Component\RuleEngine\Exceptions\UnknownRuleException;
use Oliverde8\Component\RuleEngine\RuleApplier;
use Oliverde8\Component\RuleEngine\Rules\RuleInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Class RuleApplierTest
 *
 * @author    de Cramer Oliver<oldec@smile.fr>
 * @copyright 2018 Smile
 * @package Smile\Components\RuleEngine\Tests
 */
class RuleApplierTest extends TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockRule1;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockRule2;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockLogger;

    /** @var RuleApplier */
    protected $ruleApplier;

    /**
     * @inheritdoc
     */
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRule1 = $this->getMockBuilder(RuleInterface::class)->getMock();
        $this->mockRule1->method('getRuleCode')->willReturn('rule1');

        $this->mockRule2 = $this->getMockBuilder(RuleInterface::class)->getMock();
        $this->mockRule2->method('getRuleCode')->willReturn('rule2');

        $this->mockLogger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->ruleApplier = new RuleApplier($this->mockLogger, [], true);
        $this->ruleApplier->registerRule($this->mockRule1);
        $this->ruleApplier->registerRule($this->mockRule2);
    }

    public function testApplyingSimpleRule()
    {
        $this->mockRule1
            ->expects($this->once())
            ->method('apply')
            ->with([], [], ['test'])
            ->willReturn('my value');

        $this->assertEquals(
            'my value',
            $this->ruleApplier->apply([], [], [['rule1' => ['test']]], [], ['id' => 'test'])
        );
    }

    public function testApplyConstantValue()
    {
        $this->assertEquals(
            'my value',
            $this->ruleApplier->apply([], [], 'my value', [], ['id' => 'test'])
        );
    }

    public function testApplyConsecutiveRules()
    {
        $this->mockRule1
            ->expects($this->once())
            ->method('apply')
            ->with([], [], ['test'])
            ->willReturn(null);

        $this->mockRule2
            ->expects($this->once())
            ->method('apply')
            ->with([], [], ['test'])
            ->willReturn('my value');

        $this->assertEquals(
            'my value',
            $this->ruleApplier->apply([], [], [['rule1' => ['test']], ['rule2' => ['test']]], [], ['id' => 'test'])
        );
    }

    public function testApplyConsecutiveRulesWithConstant()
    {
        $this->mockRule1
            ->expects($this->once())
            ->method('apply')
            ->willReturn(null);

        $this->assertEquals(
            'my value',
            $this->ruleApplier->apply([], [], [['rule1' => ['test']], 'my value'], [], ['id' => 'test'])
        );
    }

    public function testNoResults()
    {
        $this->mockRule1
            ->expects($this->once())
            ->method('apply')
            ->with([], [], ['test'])
            ->willReturn(null);

        $this->mockRule2
            ->expects($this->once())
            ->method('apply')
            ->with([], [], ['test'])
            ->willReturn(null);

        $this->mockLogger->expects($this->once())->method('warning');

        $this->assertEquals(
            '',
            $this->ruleApplier->apply([], [], [['rule1' => ['test']], ['rule2' => ['test']]], [], ['id' => 'test'])
        );
    }

    public function testUnknownRule()
    {
        $this->expectException(UnknownRuleException::class);

        $this->ruleApplier->apply([], [], [['rule3' => ['test']]], [], ['id' => 'test']);
    }

    public function testValidationError()
    {
        $this->mockRule1->method('validate')->willThrowException(new RuleException());
        $this->expectException(RuleException::class);
        $this->mockLogger->expects($this->once())->method('error');

        $this->ruleApplier->apply([], [], [['rule1' => ['test']]], [], ['id' => 'test']);
    }
}