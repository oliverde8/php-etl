<?php

namespace Oliverde8\Component\RuleEngine\Tests\Rules;

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

    /**
     * @inheritdoc
     */
    #[\Override]
    protected function getRule()
    {
        return new StrToUpper(new NullLogger());
    }
}