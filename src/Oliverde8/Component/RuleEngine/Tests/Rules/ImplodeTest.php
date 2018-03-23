<?php

namespace Oliverde8\Component\RuleEngine\Tests\Rules;

use Oliverde8\Component\RuleEngine\RuleApplier;
use Oliverde8\Component\RuleEngine\Rules\Implode;
use Psr\Log\NullLogger;

/**
 * Class ImplodeTest
 *
 * @author    de Cramer Oliver<oldec@smile.fr>
 * @copyright 2018 Smile
 * @package Oliverde8\Component\RuleEngine\Tests\Rules
 */
class ImplodeTest extends AbstractRule
{
    /**
     * Test simple array being imploded.
     */
    public function testImplode()
    {
        $this->assertRuleResults([], [], ['values' => ['1', '2'], 'with' => ','], '1,2');
    }

    /**
     * Test when array is contained in array.
     */
    public function testTwoLevelImplode()
    {
        $values = [['1', '2']];
        $ruleApplier = $this->getMockBuilder(RuleApplier::class)->disableOriginalConstructor()->getMock();
        $ruleApplier->method('apply')->willReturn($values);
        $this->rule->setApplier($ruleApplier);

        $this->assertRuleResults([], [], ['values' => $values, 'with' => ','], '1,2');
    }

    /**
     * Test imploding complex array.
     */
    public function testFourLevelImplode()
    {
        $values = [[[['1', '2'], ['3']], '4']];
        $ruleApplier = $this->getMockBuilder(RuleApplier::class)->disableOriginalConstructor()->getMock();
        $ruleApplier->method('apply')->willReturn($values);
        $this->rule->setApplier($ruleApplier);

        $this->assertRuleResults([], [], ['values' => $values, 'with' => ','], '1,2,3,4');
    }

    /**
     * @inheritdoc
     */
    function getRule()
    {
        return new Implode(new NullLogger());
    }
}