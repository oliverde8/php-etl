<?php

namespace Oliverde8\Component\PhpEtl\Tests\OperationConfig;

use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainMergeConfig;
use PHPUnit\Framework\TestCase;

class ChainMergeConfigTest extends TestCase
{
    public function testAddMergeWithoutNameUsesNumericIndex(): void
    {
        $chain1 = new ChainConfig();
        $chain2 = new ChainConfig();

        $config = new ChainMergeConfig();
        $config->addMerge($chain1)->addMerge($chain2);

        $this->assertSame([0 => $chain1, 1 => $chain2], $config->getChainConfigs());
    }

    public function testAddMergeWithNameUsesNameAsKey(): void
    {
        $chain = new ChainConfig();

        $config = new ChainMergeConfig();
        $config->addMerge($chain, 'premium');

        $this->assertSame(['premium' => $chain], $config->getChainConfigs());
    }

    public function testNamedAndUnnamedMergesCanBeMixed(): void
    {
        $chain1 = new ChainConfig();
        $chain2 = new ChainConfig();
        $chain3 = new ChainConfig();

        $config = new ChainMergeConfig();
        $config->addMerge($chain1, 'premium')->addMerge($chain2)->addMerge($chain3, 'regular');

        $this->assertSame(
            ['premium' => $chain1, 0 => $chain2, 'regular' => $chain3],
            $config->getChainConfigs()
        );
    }

    public function testAddMergeWithDuplicateNameThrows(): void
    {
        $config = new ChainMergeConfig();
        $config->addMerge(new ChainConfig(), 'premium');

        $this->expectException(\InvalidArgumentException::class);
        $config->addMerge(new ChainConfig(), 'premium');
    }
}
