<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Tests\OperationConfig;

use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Expression\Expression;
use Oliverde8\Component\PhpEtl\OperationConfig\SwitchConfig;
use Oliverde8\Component\RuleEngine\RuleConfig\GetRuleConfig;
use PHPUnit\Framework\TestCase;

class SwitchConfigTest extends TestCase
{
    public function testAddCaseWithRules(): void
    {
        $then = new ChainConfig();
        $config = new SwitchConfig();
        $config->addCase($then, [true]);

        $this->assertSame([
            ['rules' => [true], 'then' => $then],
        ], $config->getCases());
    }

    public function testAddCaseWithExpression(): void
    {
        $then = new ChainConfig();
        $config = new SwitchConfig();
        $expression = new Expression('data["country"] == "US"');
        $config->addCase($then, $expression);

        $this->assertSame([
            ['rules' => $expression, 'then' => $then],
        ], $config->getCases());
    }

    public function testAddCaseWithRuleConfig(): void
    {
        $then = new ChainConfig();
        $config = new SwitchConfig();
        $ruleConfig = new GetRuleConfig('country');
        $config->addCase($then, $ruleConfig);

        $this->assertSame([
            ['rules' => $ruleConfig, 'then' => $then],
        ], $config->getCases());
    }

    public function testMissingRulesThrows(): void
    {
        $config = new SwitchConfig();

        $this->expectException(\InvalidArgumentException::class);
        $config->addCase(new ChainConfig());
    }

    public function testGetDefaultChainConfig(): void
    {
        $default = new ChainConfig();
        $config = new SwitchConfig(default: $default);

        $this->assertSame($default, $config->getDefaultChainConfig());
    }

    public function testDefaultChainConfigIsNullWhenNotProvided(): void
    {
        $config = new SwitchConfig();

        $this->assertNull($config->getDefaultChainConfig());
    }

    public function testCasesAreOrderedByInsertion(): void
    {
        $then1 = new ChainConfig();
        $then2 = new ChainConfig();

        $config = new SwitchConfig();
        $config->addCase($then1, new Expression('data["country"] == "US"'))
            ->addCase($then2, new Expression('data["country"] == "FR"'));

        $cases = $config->getCases();
        $this->assertSame($then1, $cases[0]['then']);
        $this->assertSame($then2, $cases[1]['then']);
    }
}
