<?php

namespace Oliverde8\Component\RuleEngine\Tests\Rules;

use Oliverde8\Component\RuleEngine\RuleApplier;
use Oliverde8\Component\RuleEngine\RuleConfig\ConstantRuleConfig;
use Oliverde8\Component\RuleEngine\RuleConfig\StrToLowerRuleConfig;
use Oliverde8\Component\RuleEngine\Rules\StrToLower;
use Psr\Log\NullLogger;

/**
 * Class StrToLowerTest
 *
 * @author    de Cramer Oliver<oldec@smile.fr>
 * @copyright 2018 Smile
 * @package Oliverde8\Component\RuleEngine\Tests\Rules
 */
class StrToLowerTest extends AbstractRule
{
    /**
     * Test that all characters are properly uppercased.
     */
    public function testStrToLower()
    {
        $this->assertRuleResults([], [], ['value' => 'My tEsT'], 'my test');
    }

    public function testConfigStrToLower()
    {
        $ruleApplier = $this->getMockBuilder(RuleApplier::class)->disableOriginalConstructor()->getMock();
        $ruleApplier->method('applyConfig')->willReturn('My tEsT');
        $this->rule->setApplier($ruleApplier);

        $this->assertRuleConfigResults([], [], new StrToLowerRuleConfig(new ConstantRuleConfig('My tEsT')), 'my test');
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    protected function getRule()
    {
        return new StrToLower(new NullLogger());
    }
}