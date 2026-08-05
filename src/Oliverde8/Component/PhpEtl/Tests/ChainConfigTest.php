<?php

namespace Oliverde8\Component\PhpEtl\Tests;

use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\OperationConfigInterface;
use PHPUnit\Framework\TestCase;

class ChainConfigTest extends TestCase
{
    public function testAddLinkWithoutNameUsesNumericIndex(): void
    {
        $config1 = $this->createMock(OperationConfigInterface::class);
        $config2 = $this->createMock(OperationConfigInterface::class);

        $chainConfig = new ChainConfig();
        $chainConfig->addLink($config1)->addLink($config2);

        $this->assertSame([0 => $config1, 1 => $config2], $chainConfig->getConfigs());
    }

    public function testAddLinkWithNameUsesNameAsKey(): void
    {
        $config = $this->createMock(OperationConfigInterface::class);

        $chainConfig = new ChainConfig();
        $chainConfig->addLink($config, 'extract-customers');

        $this->assertSame(['extract-customers' => $config], $chainConfig->getConfigs());
    }

    public function testNamedAndUnnamedLinksCanBeMixed(): void
    {
        $config1 = $this->createMock(OperationConfigInterface::class);
        $config2 = $this->createMock(OperationConfigInterface::class);
        $config3 = $this->createMock(OperationConfigInterface::class);

        $chainConfig = new ChainConfig();
        $chainConfig->addLink($config1, 'extract')->addLink($config2)->addLink($config3, 'load');

        $this->assertSame(
            ['extract' => $config1, 0 => $config2, 'load' => $config3],
            $chainConfig->getConfigs()
        );
    }

    public function testAddLinkWithDuplicateNameThrows(): void
    {
        $config1 = $this->createMock(OperationConfigInterface::class);
        $config2 = $this->createMock(OperationConfigInterface::class);

        $chainConfig = new ChainConfig();
        $chainConfig->addLink($config1, 'extract-customers');

        $this->expectException(\InvalidArgumentException::class);
        $chainConfig->addLink($config2, 'extract-customers');
    }
}
