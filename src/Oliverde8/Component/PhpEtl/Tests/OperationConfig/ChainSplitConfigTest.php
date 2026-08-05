<?php

namespace Oliverde8\Component\PhpEtl\Tests\OperationConfig;

use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainSplitConfig;
use PHPUnit\Framework\TestCase;

class ChainSplitConfigTest extends TestCase
{
    public function testAddSplitWithoutNameUsesNumericIndex(): void
    {
        $chain1 = new ChainConfig();
        $chain2 = new ChainConfig();

        $config = new ChainSplitConfig();
        $config->addSplit($chain1)->addSplit($chain2);

        $this->assertSame([0 => $chain1, 1 => $chain2], $config->getChainConfigs());
    }

    public function testAddSplitWithNameUsesNameAsKey(): void
    {
        $chain = new ChainConfig();

        $config = new ChainSplitConfig();
        $config->addSplit($chain, 'subscribed');

        $this->assertSame(['subscribed' => $chain], $config->getChainConfigs());
    }

    public function testNamedAndUnnamedSplitsCanBeMixed(): void
    {
        $chain1 = new ChainConfig();
        $chain2 = new ChainConfig();
        $chain3 = new ChainConfig();

        $config = new ChainSplitConfig();
        $config->addSplit($chain1, 'subscribed')->addSplit($chain2)->addSplit($chain3, 'unsubscribed');

        $this->assertSame(
            ['subscribed' => $chain1, 0 => $chain2, 'unsubscribed' => $chain3],
            $config->getChainConfigs()
        );
    }

    public function testAddSplitWithDuplicateNameThrows(): void
    {
        $config = new ChainSplitConfig();
        $config->addSplit(new ChainConfig(), 'subscribed');

        $this->expectException(\InvalidArgumentException::class);
        $config->addSplit(new ChainConfig(), 'subscribed');
    }
}
