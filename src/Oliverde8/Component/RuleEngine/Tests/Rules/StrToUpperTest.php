<?php

namespace Oliverde8\Component\RuleEngine\Tests\Rules;

use Oliverde8\Component\RuleEngine\RuleApplier;
use Oliverde8\Component\RuleEngine\RuleConfig\ConstantRuleConfig;
use Oliverde8\Component\RuleEngine\RuleConfig\StrToUpperRuleConfig;
use Oliverde8\Component\RuleEngine\Rules\StrToUpper;
use Psr\Log\NullLogger;

/**
 * Class StrToLowerTest
 *
 * @author    de Cramer Oliver<oldec@smile.fr>
 * @copyright 2018 Smile
 * @package Oliverde8\Component\RuleEngine\Tests\Rules
 */
class StrToUpperTest extends AbstractRule
{
    /**
     * Test that all characters are properly uppercased.
     */
    public function testStrToUpper()
    {
        $this->assertRuleResults([], [], ['value' => 'My tEsT'], 'MY TEST');
    }

    public function testConfigStrToUpper()
    {
        $ruleApplier = $this->getMockBuilder(RuleApplier::class)->disableOriginalConstructor()->getMock();
        $ruleApplier->method('applyConfig')->willReturn('My tEsT');
        $this->rule->setApplier($ruleApplier);

        $this->assertRuleConfigResults([], [], new StrToUpperRuleConfig(new ConstantRuleConfig('My tEsT')), 'MY TEST');
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    protected function getRule()
    {
        return new StrToUpper(new NullLogger());
    }
}