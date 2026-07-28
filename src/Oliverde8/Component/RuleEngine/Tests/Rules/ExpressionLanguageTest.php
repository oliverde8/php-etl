<?php

declare(strict_types=1);

namespace Oliverde8\Component\RuleEngine\Tests\Rules;

use Oliverde8\Component\RuleEngine\RuleApplier;
use Oliverde8\Component\RuleEngine\RuleConfig\ConstantRuleConfig;
use Oliverde8\Component\RuleEngine\RuleConfig\ExpressionRuleConfig;
use Oliverde8\Component\RuleEngine\Rules\ExpressionLanguage;
use Psr\Log\NullLogger;

class ExpressionLanguageTest extends AbstractRule
{
    public function testExpressionAgainstRowData()
    {
        $this->assertRuleResults(
            ['IsSubscribed' => true],
            [],
            ['expression' => "rowData['IsSubscribed'] == true"],
            true
        );
    }

    public function testExpressionWithValues()
    {
        $ruleApplier = $this->getMockBuilder(RuleApplier::class)->disableOriginalConstructor()->getMock();
        $ruleApplier->method('apply')->willReturn('bar');
        $this->rule->setApplier($ruleApplier);

        $this->assertRuleResults(
            [],
            [],
            ['expression' => "foo == 'bar'", 'values' => ['foo' => ['rule' => ['value']]]],
            true
        );
    }

    public function testConfigExpressionAgainstRowData()
    {
        $this->assertRuleConfigResults(
            ['IsSubscribed' => true],
            [],
            new ExpressionRuleConfig("rowData['IsSubscribed'] == true"),
            true
        );
    }

    public function testConfigExpressionWithValues()
    {
        $ruleApplier = $this->getMockBuilder(RuleApplier::class)->disableOriginalConstructor()->getMock();
        $ruleApplier->method('applyConfig')->willReturn('bar');
        $this->rule->setApplier($ruleApplier);

        $this->assertRuleConfigResults(
            [],
            [],
            new ExpressionRuleConfig("foo == 'bar'", ['foo' => new ConstantRuleConfig('bar')]),
            true
        );
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    protected function getRule()
    {
        return new ExpressionLanguage(new NullLogger());
    }
}
