<?php

namespace Oliverde8\Component\RuleEngine\Tests\Rules;

use Oliverde8\Component\RuleEngine\RuleApplier;
use Oliverde8\Component\RuleEngine\Rules\RuleInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Class AbstractRule
 *
 * @author    de Cramer Oliver<oldec@smile.fr>
 * @copyright 2018 Smile
 * @package Oliverde8\Component\RuleEngine\Tests\Rules
 */
abstract class AbstractRule extends TestCase
{
    /** @var RuleInterface */
    protected $rule;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = $this->getRule();
        $ruleApplier = new RuleApplier(new NullLogger(), []);

        $this->rule->setApplier($ruleApplier);
    }

    protected function assertRuleResults($lineData, $transformedData, $options, $expected)
    {
        $this->rule->validate($options);
        $this->assertEquals($expected, $this->rule->apply($lineData, $transformedData, $options));
    }

    public function testRuleCode()
    {
        $this->assertNotEmpty($this->rule->getRuleCode());
    }

    abstract protected function getRule();
}
