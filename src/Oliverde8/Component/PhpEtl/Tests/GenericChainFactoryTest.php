<?php

namespace Oliverde8\Component\PhpEtl\Tests;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\Exception\ChainBuilderException;
use Oliverde8\Component\PhpEtl\GenericChainFactory;
use Oliverde8\Component\PhpEtl\OperationConfig\OperationConfigInterface;
use Oliverde8\Component\PhpEtl\Tests\Fixtures\TestOperationWithConfig;
use Oliverde8\Component\PhpEtl\Tests\Fixtures\TestOperationWithConfigAndInjection;
use Oliverde8\Component\PhpEtl\Tests\Fixtures\TestOperationWithConfigAndChainBuilder;
use Oliverde8\Component\PhpEtl\Tests\Fixtures\TestOperationWithDefaults;
use Oliverde8\Component\PhpEtl\Tests\Fixtures\TestOperationWithFlavor;
use Oliverde8\Component\PhpEtl\Tests\Fixtures\TestOperationConfig;
use Oliverde8\Component\PhpEtl\Tests\Fixtures\TestNonConfigurableOperation;
use PHPUnit\Framework\TestCase;

class GenericChainFactoryTest extends TestCase
{
    private ChainBuilderV2 $chainBuilder;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->chainBuilder = $this->createMock(ChainBuilderV2::class);
    }

    public function testConstructorThrowsExceptionForNonConfigurableOperation()
    {
        $this->expectException(ChainBuilderException::class);
        $this->expectExceptionMessage('must implement ConfigurableChainOperationInterface');

        new GenericChainFactory(
            TestNonConfigurableOperation::class,
            TestOperationConfig::class
        );
    }

    public function testSupportsReturnsTrueForMatchingConfigAndFlavor()
    {
        $factory = new GenericChainFactory(
            TestOperationWithConfig::class,
            TestOperationConfig::class,
            'default'
        );

        $config = new TestOperationConfig('default');

        $this->assertTrue($factory->supports($config));
    }

    public function testSupportsReturnsFalseForDifferentConfigClass()
    {
        $factory = new GenericChainFactory(
            TestOperationWithConfig::class,
            TestOperationConfig::class,
            'default'
        );

        $config = $this->createMock(OperationConfigInterface::class);
        $config->method('getFlavor')->willReturn('default');

        $this->assertFalse($factory->supports($config));
    }

    public function testSupportsReturnsFalseForDifferentFlavor()
    {
        $factory = new GenericChainFactory(
            TestOperationWithConfig::class,
            TestOperationConfig::class,
            'custom'
        );

        $config = new TestOperationConfig('default');

        $this->assertFalse($factory->supports($config));
    }

    public function testSupportsReturnsTrueForMatchingSubclass()
    {
        $factory = new GenericChainFactory(
            TestOperationWithConfig::class,
            OperationConfigInterface::class,
            'default'
        );

        $config = new TestOperationConfig('default');

        $this->assertTrue($factory->supports($config));
    }

    public function testBuildCreatesOperationWithConfig()
    {
        $factory = new GenericChainFactory(
            TestOperationWithConfig::class,
            TestOperationConfig::class
        );

        $config = new TestOperationConfig('default');

        $operation = $factory->build($config, $this->chainBuilder);

        $this->assertInstanceOf(TestOperationWithConfig::class, $operation);
        $this->assertSame($config, $operation->getConfig());
    }

    public function testBuildInjectsChainBuilder()
    {
        $factory = new GenericChainFactory(
            TestOperationWithConfigAndChainBuilder::class,
            TestOperationConfig::class
        );

        $config = new TestOperationConfig('default');

        $operation = $factory->build($config, $this->chainBuilder);

        $this->assertInstanceOf(TestOperationWithConfigAndChainBuilder::class, $operation);
        $this->assertSame($this->chainBuilder, $operation->getChainBuilder());
        $this->assertSame($config, $operation->getConfig());
    }

    public function testBuildInjectsCustomDependencies()
    {
        $injectedService = new \stdClass();
        $injectedService->value = 'test';

        $factory = new GenericChainFactory(
            TestOperationWithConfigAndInjection::class,
            TestOperationConfig::class,
            injections: ['service' => $injectedService]
        );

        $config = new TestOperationConfig('default');

        $operation = $factory->build($config, $this->chainBuilder);

        $this->assertInstanceOf(TestOperationWithConfigAndInjection::class, $operation);
        $this->assertSame($injectedService, $operation->getService());
    }

    public function testBuildInjectsFlavor()
    {
        $factory = new GenericChainFactory(
            TestOperationWithFlavor::class,
            TestOperationConfig::class,
            flavor: 'custom'
        );

        $config = new TestOperationConfig('custom');

        $operation = $factory->build($config, $this->chainBuilder);

        $this->assertInstanceOf(TestOperationWithFlavor::class, $operation);
        $this->assertEquals('custom', $operation->getFlavor());
    }

    public function testBuildUsesDefaultParameterValues()
    {
        $factory = new GenericChainFactory(
            TestOperationWithDefaults::class,
            TestOperationConfig::class
        );

        $config = new TestOperationConfig('default');

        $operation = $factory->build($config, $this->chainBuilder);

        $this->assertInstanceOf(TestOperationWithDefaults::class, $operation);
        $this->assertEquals('default_value', $operation->getOptionalParam());
    }

    public function testBuildThrowsExceptionForMissingParameter()
    {
        $factory = new GenericChainFactory(
            TestOperationWithConfigAndInjection::class,
            TestOperationConfig::class
        );

        $config = new TestOperationConfig('default');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing parameter 'service'");

        $factory->build($config, $this->chainBuilder);
    }

    public function testBuildInjectsMultipleParameters()
    {
        $service1 = new \stdClass();
        $service2 = new \stdClass();

        $factory = new GenericChainFactory(
            TestOperationWithConfigAndInjection::class,
            TestOperationConfig::class,
            injections: ['service' => $service1, 'unused' => $service2]
        );

        $config = new TestOperationConfig('default');

        $operation = $factory->build($config, $this->chainBuilder);

        $this->assertSame($service1, $operation->getService());
    }

    public function testBuildWithNoConstructor()
    {
        $factory = new GenericChainFactory(
            TestOperationWithConfig::class,
            TestOperationConfig::class
        );

        $config = new TestOperationConfig('default');

        $operation = $factory->build($config, $this->chainBuilder);

        $this->assertInstanceOf(ConfigurableChainOperationInterface::class, $operation);
    }

    public function testSupportsChecksFlavorMatch()
    {
        $factory = new GenericChainFactory(
            TestOperationWithConfig::class,
            TestOperationConfig::class,
            'production'
        );

        $defaultConfig = new TestOperationConfig('default');
        $productionConfig = new TestOperationConfig('production');

        $this->assertFalse($factory->supports($defaultConfig));
        $this->assertTrue($factory->supports($productionConfig));
    }

    public function testConstructorValidatesOperationClass()
    {
        $this->expectException(ChainBuilderException::class);

        new GenericChainFactory(
            'NonExistentClass',
            TestOperationConfig::class
        );
    }
}

