<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Tests\OperationConfig;

use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\SwitchConfig;
use PHPUnit\Framework\TestCase;

class SwitchConfigTest extends TestCase
{
    public function testAddCaseWithRules(): void
    {
        $then = new ChainConfig();
        $config = new SwitchConfig();
        $config->addCase($then, rules: [true]);

        $this->assertSame([
            ['rules' => [true], 'expression' => null, 'then' => $then],
        ], $config->getCases());
    }

    public function testAddCaseWithExpression(): void
    {
        $then = new ChainConfig();
        $config = new SwitchConfig();
        $config->addCase($then, expression: 'data["country"] == "US"');

        $this->assertSame([
            ['rules' => [], 'expression' => 'data["country"] == "US"', 'then' => $then],
        ], $config->getCases());
    }

    public function testMissingRulesAndExpressionThrows(): void
    {
        $config = new SwitchConfig();

        $this->expectException(\InvalidArgumentException::class);
        $config->addCase(new ChainConfig());
    }

    public function testRulesAndExpressionAreMutuallyExclusive(): void
    {
        $config = new SwitchConfig();

        $this->expectException(\InvalidArgumentException::class);
        $config->addCase(new ChainConfig(), rules: [true], expression: 'data["a"] == true');
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
        $config->addCase($then1, expression: 'data["country"] == "US"')
            ->addCase($then2, expression: 'data["country"] == "FR"');

        $cases = $config->getCases();
        $this->assertSame($then1, $cases[0]['then']);
        $this->assertSame($then2, $cases[1]['then']);
    }
}
