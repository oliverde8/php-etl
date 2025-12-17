<?php
/**
 * File RuleTransformOperationTest.php
 *
 * @author    de Cramer Oliver<oldec@smile.fr>
 * @copyright 2018 Smile
 */

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation\Transformer;

use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\RuleTransformOperation;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem;
use Oliverde8\Component\RuleEngine\RuleApplier;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RuleTransformOperationTest extends TestCase
{
    /** @var MockObject */
    protected $ruleApplierMock;

    /** @var MockObject */
    protected $context;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->ruleApplierMock = $this->getMockBuilder(RuleApplier::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context = new ExecutionContext([], new LocalFileSystem());
    }

    public function testMultiColumnRulesAddingData()
    {
        $this->ruleApplierMock->expects($this->exactly(3))->method('apply')->willReturn(1);

        $transform = new RuleTransformOperation(
            $this->ruleApplierMock,
            ['test1' => ['rules' => ''], 'test2' => ['rules' => ''], 'test3' => ['rules' => '']],
            true
        );

        $data = new DataItem(['test' => 'test']);
        $data = $transform->process($data, $this->context);

        $this->assertEquals(['test' => 'test', 'test1' => 1, 'test2' => 1, 'test3' => 1], $data->getData());
    }

    public function testMultiColumnRulesReplacingData()
    {
        $this->ruleApplierMock->expects($this->exactly(3))->method('apply')->willReturn(1);

        $transform = new RuleTransformOperation(
            $this->ruleApplierMock,
            ['test1' => ['rules' => ''], 'test2' => ['rules' => ''], 'test3' => ['rules' => '']],
            false
        );

        $data = new DataItem(['test' => 'test']);
        $data = $transform->process($data, $this->context);

        $this->assertEquals(['test1' => 1, 'test2' => 1, 'test3' => 1], $data->getData());
    }

    public function testDynamicColumn()
    {
        $this->ruleApplierMock->expects($this->exactly(2))->method('apply')->willReturn(1);

        $context = clone $this->context;
        $context->setParameter('locales', ['fr_FR', 'en_GB']);
        $transform = new RuleTransformOperation(
            $this->ruleApplierMock,
            ['test1-{@context/locales}' => ['rules' => '']],
            false
        );

        $data = new DataItem(['test' => 'test']);
        $data = $transform->process($data,$context);

        $this->assertEquals(['test1-fr_FR' => 1, 'test1-en_GB' => 1], $data->getData());
    }

    public function testMultipleDynamic()
    {
        $this->ruleApplierMock->expects($this->exactly(4))->method('apply')->willReturn(1);

        $context = clone $this->context;
        $context->setParameter('locales', ['fr_FR', 'en_GB']);
        $context->setParameter('scopes', ['master', 'mobile']);

        $transform = new RuleTransformOperation(
            $this->ruleApplierMock,
            ['test1-{@context/scopes}-{@context/locales}' => ['rules' => '']],
            false
        );

        $data = new DataItem(['test' => 'test']);
        $data = $transform->process($data,$context);

        $this->assertEquals(
            ['test1-master-fr_FR' => 1, 'test1-master-en_GB' => 1, 'test1-mobile-fr_FR' => 1, 'test1-mobile-en_GB' => 1],
            $data->getData()
        );
    }

    public function testSimpleDynamic()
    {
        $this->ruleApplierMock->expects($this->exactly(1))->method('apply')->willReturn(1);


        $context = clone $this->context;
        $context->setParameter('locales', ['fr_FR']);
        $transform = new RuleTransformOperation(
            $this->ruleApplierMock,
            ['test1-{@context/locales}' => ['rules' => '']],
            false
        );

        $data = new DataItem(['test' => 'test']);
        $data = $transform->process($data,$context);

        $this->assertEquals(
            ['test1-fr_FR' => 1],
            $data->getData()
        );
    }
}
