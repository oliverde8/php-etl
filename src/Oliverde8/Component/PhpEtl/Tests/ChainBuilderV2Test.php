<?php

namespace Oliverde8\Component\PhpEtl\Tests;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainProcessorInterface;
use Oliverde8\Component\PhpEtl\ExecutionContextFactory;
use Oliverde8\Component\PhpEtl\GenericChainFactory;
use Oliverde8\Component\PhpEtl\OperationConfig\OperationConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChainBuilderV2Test extends TestCase
{
    private ExecutionContextFactory $contextFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->contextFactory = new ExecutionContextFactory();
    }

    public function testCreateChainWithNoFactories()
    {
        $chainBuilder = new ChainBuilderV2($this->contextFactory, []);
        $chainConfig = new ChainConfig();

        $chain = $chainBuilder->createChain($chainConfig);

        $this->assertInstanceOf(ChainProcessorInterface::class, $chain);
    }

    public function testCreateChainWithSingleOperation()
    {
        $mockConfig = $this->createMock(OperationConfigInterface::class);
        $mockConfig->method('getFlavor')->willReturn('default');

        $mockOperation = $this->createMock(ConfigurableChainOperationInterface::class);

        $mockFactory = $this->createMock(GenericChainFactory::class);
        $mockFactory->expects($this->once())
            ->method('supports')
            ->with($mockConfig)
            ->willReturn(true);
        $mockFactory->expects($this->once())
            ->method('build')
            ->with($mockConfig, $this->isInstanceOf(ChainBuilderV2::class))
            ->willReturn($mockOperation);

        $chainBuilder = new ChainBuilderV2($this->contextFactory, [$mockFactory]);

        $chainConfig = new ChainConfig();
        $chainConfig->addLink($mockConfig);

        $chain = $chainBuilder->createChain($chainConfig);

        $this->assertInstanceOf(ChainProcessorInterface::class, $chain);
    }

    public function testCreateChainWithMultipleOperations()
    {
        $mockConfig1 = $this->createMock(OperationConfigInterface::class);
        $mockConfig1->method('getFlavor')->willReturn('default');

        $mockConfig2 = $this->createMock(OperationConfigInterface::class);
        $mockConfig2->method('getFlavor')->willReturn('default');

        $mockOperation1 = $this->createMock(ConfigurableChainOperationInterface::class);
        $mockOperation2 = $this->createMock(ConfigurableChainOperationInterface::class);

        $mockFactory = $this->createMock(GenericChainFactory::class);
        $mockFactory->expects($this->exactly(2))
            ->method('supports')
            ->willReturn(true);
        $mockFactory->expects($this->exactly(2))
            ->method('build')
            ->willReturnOnConsecutiveCalls($mockOperation1, $mockOperation2);

        $chainBuilder = new ChainBuilderV2($this->contextFactory, [$mockFactory]);

        $chainConfig = new ChainConfig();
        $chainConfig->addLink($mockConfig1)->addLink($mockConfig2);

        $chain = $chainBuilder->createChain($chainConfig);

        $this->assertInstanceOf(ChainProcessorInterface::class, $chain);
    }

    public function testCreateChainWithMultipleFactories()
    {
        $mockConfig1 = $this->createMock(OperationConfigInterface::class);
        $mockConfig1->method('getFlavor')->willReturn('default');

        $mockConfig2 = $this->createMock(OperationConfigInterface::class);
        $mockConfig2->method('getFlavor')->willReturn('custom');

        $mockOperation1 = $this->createMock(ConfigurableChainOperationInterface::class);
        $mockOperation2 = $this->createMock(ConfigurableChainOperationInterface::class);

        $mockFactory1 = $this->createMock(GenericChainFactory::class);
        $mockFactory1->method('supports')
            ->willReturnCallback(fn($config) => $config === $mockConfig1);
        $mockFactory1->expects($this->once())
            ->method('build')
            ->with($mockConfig1)
            ->willReturn($mockOperation1);

        $mockFactory2 = $this->createMock(GenericChainFactory::class);
        $mockFactory2->method('supports')
            ->willReturnCallback(fn($config) => $config === $mockConfig2);
        $mockFactory2->expects($this->once())
            ->method('build')
            ->with($mockConfig2)
            ->willReturn($mockOperation2);

        $chainBuilder = new ChainBuilderV2($this->contextFactory, [$mockFactory1, $mockFactory2]);

        $chainConfig = new ChainConfig();
        $chainConfig->addLink($mockConfig1)->addLink($mockConfig2);

        $chain = $chainBuilder->createChain($chainConfig);

        $this->assertInstanceOf(ChainProcessorInterface::class, $chain);
    }

    public function testCreateChainWithFactoryNotSupporting()
    {
        $mockConfig = $this->createMock(OperationConfigInterface::class);
        $mockConfig->method('getFlavor')->willReturn('default');

        $mockFactory = $this->createMock(GenericChainFactory::class);
        $mockFactory->method('supports')->willReturn(false);

        $chainBuilder = new ChainBuilderV2($this->contextFactory, [$mockFactory]);

        $chainConfig = new ChainConfig();
        $chainConfig->addLink($mockConfig);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No factory found');

        $chainBuilder->createChain($chainConfig);
    }

    public function testCreateChainWithNoMatchingFactory()
    {
        $mockConfig = $this->createMock(OperationConfigInterface::class);
        $mockConfig->method('getFlavor')->willReturn('default');

        $chainBuilder = new ChainBuilderV2($this->contextFactory, []);

        $chainConfig = new ChainConfig();
        $chainConfig->addLink($mockConfig);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/No factory found.*' . preg_quote($mockConfig::class) . '/');

        $chainBuilder->createChain($chainConfig);
    }

    public function testCreateChainWithMaxAsynchronousItems()
    {
        $chainBuilder = new ChainBuilderV2($this->contextFactory, []);
        $chainConfig = new ChainConfig(maxAsynchronousItems: 5);

        $chain = $chainBuilder->createChain($chainConfig);

        $this->assertInstanceOf(ChainProcessorInterface::class, $chain);
    }

    public function testFactoryReceivesChainBuilderInstance()
    {
        $mockConfig = $this->createMock(OperationConfigInterface::class);
        $mockConfig->method('getFlavor')->willReturn('default');

        $mockOperation = $this->createMock(ConfigurableChainOperationInterface::class);

        $chainBuilderReceived = null;

        $mockFactory = $this->createMock(GenericChainFactory::class);
        $mockFactory->method('supports')->willReturn(true);
        $mockFactory->expects($this->once())
            ->method('build')
            ->willReturnCallback(function($config, $builder) use ($mockOperation, &$chainBuilderReceived) {
                $chainBuilderReceived = $builder;
                return $mockOperation;
            });

        $chainBuilder = new ChainBuilderV2($this->contextFactory, [$mockFactory]);

        $chainConfig = new ChainConfig();
        $chainConfig->addLink($mockConfig);

        $chainBuilder->createChain($chainConfig);

        $this->assertSame($chainBuilder, $chainBuilderReceived);
    }

    public function testCreateChainWithIterableFactories()
    {
        $mockConfig = $this->createMock(OperationConfigInterface::class);
        $mockConfig->method('getFlavor')->willReturn('default');

        $mockOperation = $this->createMock(ConfigurableChainOperationInterface::class);

        $factoryGenerator = function() use ($mockConfig, $mockOperation) {
            $mockFactory = $this->createMock(GenericChainFactory::class);
            $mockFactory->method('supports')->willReturn(true);
            $mockFactory->method('build')->willReturn($mockOperation);
            yield $mockFactory;
        };

        $chainBuilder = new ChainBuilderV2($this->contextFactory, $factoryGenerator());

        $chainConfig = new ChainConfig();
        $chainConfig->addLink($mockConfig);

        $chain = $chainBuilder->createChain($chainConfig);

        $this->assertInstanceOf(ChainProcessorInterface::class, $chain);
    }

    public function testCreateChainPreservesOperationOrder()
    {
        $configs = [];
        $operations = [];

        for ($i = 0; $i < 3; $i++) {
            $configs[$i] = $this->createMock(OperationConfigInterface::class);
            $configs[$i]->method('getFlavor')->willReturn('default');
            $operations[$i] = $this->createMock(ConfigurableChainOperationInterface::class);
        }

        $callOrder = [];

        $mockFactory = $this->createMock(GenericChainFactory::class);
        $mockFactory->method('supports')->willReturn(true);
        $mockFactory->method('build')
            ->willReturnCallback(function($config) use ($configs, $operations, &$callOrder) {
                $index = array_search($config, $configs, true);
                $callOrder[] = $index;
                return $operations[$index];
            });

        $chainBuilder = new ChainBuilderV2($this->contextFactory, [$mockFactory]);

        $chainConfig = new ChainConfig();
        foreach ($configs as $config) {
            $chainConfig->addLink($config);
        }

        $chainBuilder->createChain($chainConfig);

        $this->assertEquals([0, 1, 2], $callOrder);
    }
}

