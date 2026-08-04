<?php

namespace Oliverde8\Component\PhpEtl\Tests\Output;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainMergeOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainRepeatOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainSplitOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\FailSafeOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\CallbackTransformerOperation;
use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\ExecutionContextFactory;
use Oliverde8\Component\PhpEtl\GenericChainFactory;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainMergeConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainRepeatConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainSplitConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\FailSafeConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use Oliverde8\Component\PhpEtl\Output\MermaidStaticOutput;
use PHPUnit\Framework\TestCase;

class MermaidStaticOutputTest extends TestCase
{
    private ChainBuilderV2 $chainBuilder;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->chainBuilder = new ChainBuilderV2(
            new ExecutionContextFactory(),
            [new GenericChainFactory(CallbackTransformerOperation::class, CallBackTransformerConfig::class)]
        );
    }

    private function noop(): \Closure
    {
        return fn(ItemInterface $item) => $item;
    }

    public function testSplitBranchesAreStillRenderedAsNodes(): void
    {
        $branch1 = new ChainConfig();
        $branch1->addLink(new CallBackTransformerConfig($this->noop()));

        $splitConfig = new ChainSplitConfig();
        $splitConfig->addSplit($branch1);

        $splitOperation = new ChainSplitOperation($this->chainBuilder, $splitConfig);

        $chainProcessor = new ChainProcessor(
            ['split-step' => $splitOperation],
            new ExecutionContextFactory()
        );

        $text = (new MermaidStaticOutput())->generateGrapText($chainProcessor);

        $this->assertStringContainsString('split-step', $text);
        $this->assertStringContainsString('shape: hex', $text);
        $this->assertStringContainsString('00B-->0000B', $text);
    }

    public function testMergeBranchesAreRenderedAsNodes(): void
    {
        $branch1 = new ChainConfig();
        $branch1->addLink(new CallBackTransformerConfig($this->noop()));

        $mergeConfig = new ChainMergeConfig();
        $mergeConfig->addMerge($branch1);

        $mergeOperation = new ChainMergeOperation($this->chainBuilder, $mergeConfig);

        $chainProcessor = new ChainProcessor(
            ['merge-step' => $mergeOperation],
            new ExecutionContextFactory()
        );

        $text = (new MermaidStaticOutput())->generateGrapText($chainProcessor);

        $this->assertStringContainsString('merge-step', $text);
        $this->assertStringContainsString('shape: hex', $text);
        $this->assertStringContainsString('00B-->0000B', $text);
    }

    public function testRepeatSubChainIsRenderedAsNodes(): void
    {
        $subChain = new ChainConfig();
        $subChain->addLink(new CallBackTransformerConfig($this->noop()));

        $repeatOperation = new ChainRepeatOperation(
            $this->chainBuilder,
            new ChainRepeatConfig($subChain, 'true')
        );

        $chainProcessor = new ChainProcessor(
            ['repeat-step' => $repeatOperation],
            new ExecutionContextFactory()
        );

        $text = (new MermaidStaticOutput())->generateGrapText($chainProcessor);

        $this->assertStringContainsString('repeat-step', $text);
        $this->assertStringContainsString('shape: hex', $text);
        $this->assertStringContainsString('00B-->0000B', $text);
    }

    public function testFailSafeSubChainIsRenderedAsNodes(): void
    {
        $subChain = new ChainConfig();
        $subChain->addLink(new CallBackTransformerConfig($this->noop()));

        $failSafeOperation = new FailSafeOperation(
            $this->chainBuilder,
            new FailSafeConfig($subChain)
        );

        $chainProcessor = new ChainProcessor(
            ['fail-safe-step' => $failSafeOperation],
            new ExecutionContextFactory()
        );

        $text = (new MermaidStaticOutput())->generateGrapText($chainProcessor);

        $this->assertStringContainsString('fail-safe-step', $text);
        $this->assertStringContainsString('shape: hex', $text);
        $this->assertStringContainsString('00B-->0000B', $text);
    }
}
