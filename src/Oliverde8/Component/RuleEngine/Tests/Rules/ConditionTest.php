<?php

namespace Oliverde8\Component\RuleEngine\Tests\Rules;

use Oliverde8\Component\RuleEngine\Exceptions\Condition\UnSupportedOperationException;
use Oliverde8\Component\RuleEngine\Exceptions\RuleOptionMissingException;
use Oliverde8\Component\RuleEngine\Rules\Condition;
use Psr\Log\NullLogger;

/**
 * Class ConditionTest
 *
 * @author    de Cramer Oliver<oldec@smile.fr>
 * @copyright 2018 Smile
 * @package Oliverde8\Component\RuleEngine\Tests\Rules
 */
class ConditionTest extends AbstractRule
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockRuleApplier;

    protected function setUp(): void
    {
        $this->mockRuleApplier = new TestRuleApplier(new NullLogger(), [], false);

        parent::setUp();
    }

    /**
     * Test that the expected results is returned in all cases.
     *
     * @dataProvider constraintsAndResults
     */
    public function testConstraints($options, $expected = '')
    {
        $this->rule->setApplier($this->mockRuleApplier);

        $options['then'] = 'true';
        $options['else'] = 'false';

        $data = [];
        $this->assertRuleResults([], $data, $options, $expected);
    }

    /**
     * Test that proper exception is thrown when operation is invalid.
     */
    public function testInvalidOperation()
    {
        $this->expectException(UnSupportedOperationException::class);

        $data = [];
        $this->rule->apply([], $data, ['if' => 1, 'value' => 1, 'operation' => 'toto']);
    }

    /**
     * Test that the rules code is the one expected.
     */
    public function testRuleCode()
    {
        $this->assertEquals('condition', $this->rule->getRuleCode());
    }

    /**
     * Test that if options are missing proper exception is thrown.
     */
    public function testMissingOption()
    {
        $this->expectException(RuleOptionMissingException::class);
        $this->rule->validate(['test']);
    }

    /**
     * List of text cases.
     *
     * @return array
     */
    public function constraintsAndResults()
    {
        return [
            [['if' => 1, 'value' => 2,      'operation' => 'eq', ], 'false'],
            [['if' => 1, 'value' => 1,      'operation' => 'eq', ], 'true'],
            [['if' => 1, 'value' => 1,      'operation' => 'neq',], 'false'],
            [['if' => 1, 'value' => [1, 2], 'operation' => 'in', ], 'true'],
            [['if' => 3, 'value' => [1, 2], 'operation' => 'in', ], 'false'],
        ];
    }

    /**
     * @inheritdoc
     */
    function getRule()
    {
        return new Condition(new NullLogger());
    }
}