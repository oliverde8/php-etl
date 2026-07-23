<?php

namespace Oliverde8\Component\PhpEtl\Tests\Output;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainMergeOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainSplitOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\CallbackTransformerOperation;
use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\ExecutionContextFactory;
use Oliverde8\Component\PhpEtl\GenericChainFactory;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainMergeConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainSplitConfig;
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

    public function testMergeBranchesAreRenderedAsNodes(): void
    {
        $branch1 = new ChainConfig();
        $branch1->addLink(new CallBackTransformerConfig($this->noop()), 'in-branch-one');

        $branch2 = new ChainConfig();
        $branch2->addLink(new CallBackTransformerConfig($this->noop()), 'in-branch-two');

        $mergeConfig = new ChainMergeConfig();
        $mergeConfig->addMerge($branch1)->addMerge($branch2);

        $mergeOperation = new ChainMergeOperation($this->chainBuilder, $mergeConfig);

        $chainProcessor = new ChainProcessor(
            ['merge-step' => $mergeOperation],
            new ExecutionContextFactory()
        );

        $text = (new MermaidStaticOutput())->generateGrapText($chainProcessor);

        $this->assertStringContainsString('merge-step', $text);
        $this->assertStringContainsString('in-branch-one', $text);
        $this->assertStringContainsString('in-branch-two', $text);
        $this->assertStringContainsString('shape: hex', $text);
    }

    public function testSplitBranchesAreStillRenderedAsNodes(): void
    {
        $branch1 = new ChainConfig();
        $branch1->addLink(new CallBackTransformerConfig($this->noop()), 'in-split-branch');

        $splitConfig = new ChainSplitConfig();
        $splitConfig->addSplit($branch1);

        $splitOperation = new ChainSplitOperation($this->chainBuilder, $splitConfig);

        $chainProcessor = new ChainProcessor(
            ['split-step' => $splitOperation],
            new ExecutionContextFactory()
        );

        $text = (new MermaidStaticOutput())->generateGrapText($chainProcessor);

        $this->assertStringContainsString('split-step', $text);
        $this->assertStringContainsString('in-split-branch', $text);
    }
}
