<?php
/**
 * File ConstantTest.php
 *
 * @author    de Cramer Oliver<oldec@smile.fr>
 * @copyright 2018 Smile
 */

namespace Oliverde8\Component\RuleEngine\Tests\Rules;

use Oliverde8\Component\RuleEngine\Rules\Constant;
use Psr\Log\NullLogger;

class ConstantTest extends AbstractRule
{

    public function testConstants()
    {
        $this->assertRuleResults(['test' => 'toto 1'], [], ['value' => 'test'], 'test');
        $this->assertRuleResults(['test' => 'toto 1'], [], ['value' => ['1' => ['toto']]], ['1' => ['toto']]);
    }

    protected function getRule()
    {
        return new Constant(new NullLogger());
    }
}
