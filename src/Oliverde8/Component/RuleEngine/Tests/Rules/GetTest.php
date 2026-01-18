<?php

namespace Oliverde8\Component\RuleEngine\Tests\Rules;

use Oliverde8\Component\RuleEngine\Rules\Get;
use Psr\Log\NullLogger;

/**
 * Class ValueGetterTest
 *
 * @author    de Cramer Oliver<oldec@smile.fr>
 * @copyright 2018 Smile
 * @package Oliverde8\Component\RuleEngine\Tests\Rules
 */
class GetTest extends AbstractRule
{
    /**
     * Test fetching a simple value without locale information.
     */
    public function testWithoutLocale()
    {
        $this->assertRuleResults(['test' => 'toto 1'], [], ['field' => 'test'], 'toto 1');
    }

    /**
     * Test results when column don't exist.
     */
    public function testUnfound()
    {
        $this->assertRuleResults(['test' => 'toto 1'], [], ['field' => 'test-1'], null);
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    protected function getRule()
    {
        return new Get(new NullLogger());
    }
}