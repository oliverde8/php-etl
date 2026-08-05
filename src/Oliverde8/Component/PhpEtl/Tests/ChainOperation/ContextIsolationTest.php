<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainMergeOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainRepeatOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainSplitOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\FailSafeOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\CallbackTransformerOperation;
use Oliverde8\Component\PhpEtl\ExecutionContextFactory;
use Oliverde8\Component\PhpEtl\GenericChainFactory;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainMergeConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainRepeatConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainSplitConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\FailSafeConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\CallBackTransformerConfig;
use PHPUnit\Framework\TestCase;

class ContextIsolationTest extends TestCase
{
    private ExecutionContext $context;
    private ChainBuilderV2 $chainBuilder;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->context = new ExecutionContext([], new LocalFileSystem());
        $this->chainBuilder = new ChainBuilderV2(
            new ExecutionContextFactory(),
            [new GenericChainFactory(CallbackTransformerOperation::class, CallBackTransformerConfig::class)]
        );
    }

    private function setParamCallback(string $key, $value): CallBackTransformerConfig
    {
        return new CallBackTransformerConfig(function (ItemInterface $item, ExecutionContext $context) use ($key, $value) {
            $context->setParameter($key, $value);
            return $item;
        });
    }

    public function testSplitWithoutIsolationLeaksParameterToParent(): void
    {
        $branch = new ChainConfig();
        $branch->addLink($this->setParamCallback('touched', true));

        $splitConfig = new ChainSplitConfig();
        $splitConfig->addSplit($branch);

        $operation = new ChainSplitOperation($this->chainBuilder, $splitConfig);
        $operation->process(new DataItem([]), $this->context);

        $this->assertTrue($this->context->getParameter('touched'));
    }

    public function testSplitWithIsolationDoesNotLeakParameterToParent(): void
    {
        $branch = new ChainConfig();
        $branch->addLink($this->setParamCallback('touched', true));

        $splitConfig = new ChainSplitConfig(isolateContext: true);
        $splitConfig->addSplit($branch);

        $operation = new ChainSplitOperation($this->chainBuilder, $splitConfig);
        $operation->process(new DataItem([]), $this->context);

        $this->assertNull($this->context->getParameter('touched'));
    }

    public function testSplitWithIsolationDoesNotLeakParameterBetweenBranches(): void
    {
        $seenInBranch2 = null;

        $branch1 = new ChainConfig();
        $branch1->addLink($this->setParamCallback('touched', true));

        $branch2 = new ChainConfig();
        $branch2->addLink(new CallBackTransformerConfig(function (ItemInterface $item, ExecutionContext $context) use (&$seenInBranch2) {
            $seenInBranch2 = $context->getParameter('touched');
            return $item;
        }));

        $splitConfig = new ChainSplitConfig(isolateContext: true);
        $splitConfig->addSplit($branch1)->addSplit($branch2);

        $operation = new ChainSplitOperation($this->chainBuilder, $splitConfig);
        $operation->process(new DataItem([]), $this->context);

        $this->assertNull($seenInBranch2);
    }

    public function testMergeWithIsolationDoesNotLeakParameterToParent(): void
    {
        $branch = new ChainConfig();
        $branch->addLink($this->setParamCallback('touched', true));

        $mergeConfig = new ChainMergeConfig(isolateContext: true);
        $mergeConfig->addMerge($branch);

        $operation = new ChainMergeOperation($this->chainBuilder, $mergeConfig);
        $operation->process(new DataItem([]), $this->context);

        $this->assertNull($this->context->getParameter('touched'));
    }

    public function testMergeWithoutIsolationLeaksParameterToParent(): void
    {
        $branch = new ChainConfig();
        $branch->addLink($this->setParamCallback('touched', true));

        $mergeConfig = new ChainMergeConfig();
        $mergeConfig->addMerge($branch);

        $operation = new ChainMergeOperation($this->chainBuilder, $mergeConfig);
        $operation->process(new DataItem([]), $this->context);

        $this->assertTrue($this->context->getParameter('touched'));
    }

    public function testFailSafeWithIsolationDoesNotLeakParameterToParent(): void
    {
        $subChain = new ChainConfig();
        $subChain->addLink($this->setParamCallback('touched', true));

        $failSafeConfig = new FailSafeConfig($subChain, isolateContext: true);

        $operation = new FailSafeOperation($this->chainBuilder, $failSafeConfig);
        $operation->process(new DataItem([]), $this->context);

        $this->assertNull($this->context->getParameter('touched'));
    }

    public function testRepeatWithIsolationDoesNotLeakParameterToParentButPersistsAcrossIterations(): void
    {
        // Repeat always re-processes the same original input item; operations inside the repeated
        // subchain must rely on the execution context (not item data) to know which iteration they're on.
        $seenOnSecondIteration = null;

        $subChain = new ChainConfig();
        $subChain->addLink(new CallBackTransformerConfig(function (ItemInterface $item, ExecutionContext $context) use (&$seenOnSecondIteration) {
            if (!$context->getParameter('touched')) {
                $context->setParameter('touched', true);
                return new DataItem(['step' => 1]);
            }

            $seenOnSecondIteration = $context->getParameter('touched');
            return new DataItem(['step' => 2]);
        }));

        $repeatConfig = new ChainRepeatConfig($subChain, 'data["step"] != 2', isolateContext: true);

        $operation = new ChainRepeatOperation($this->chainBuilder, $repeatConfig);
        $result = $operation->process(new DataItem(['step' => 0]), $this->context);

        // Drain the generator so the whole repeat loop actually runs.
        foreach ($result->getIterator() as $ignored) {}

        $this->assertTrue($seenOnSecondIteration, 'Parameter set on the first iteration should still be visible on the second.');
        $this->assertNull($this->context->getParameter('touched'), 'Parameter set inside the repeat should not leak to the parent context.');
    }
}
