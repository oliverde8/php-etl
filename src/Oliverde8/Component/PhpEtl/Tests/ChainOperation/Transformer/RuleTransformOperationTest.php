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
use Oliverde8\Component\RuleEngine\RuleApplier;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RuleTransformOperationTest extends TestCase
{
    /** @var MockObject */
    protected $ruleApplierMock;

    protected function setUp()
    {
        parent::setUp();

        $this->ruleApplierMock = $this->getMockBuilder(RuleApplier::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testMultiColumnRulesAddingData()
    {
        $this->ruleApplierMock->expects($this->exactly(3))->method('apply')->willReturn(1);
        $context = [];

        $transform = new RuleTransformOperation(
            $this->ruleApplierMock,
            ['test1' => ['rules' => ''], 'test2' => ['rules' => ''], 'test3' => ['rules' => '']],
            true
        );

        $data = new DataItem(['test' => 'test']);
        $data = $transform->process($data,$context);

        $this->assertEquals(['test' => 'test', 'test1' => 1, 'test2' => 1, 'test3' => 1], $data->getData());
    }

    public function testMultiColumnRulesReplacingData()
    {
        $this->ruleApplierMock->expects($this->exactly(3))->method('apply')->willReturn(1);
        $context = [];

        $transform = new RuleTransformOperation(
            $this->ruleApplierMock,
            ['test1' => ['rules' => ''], 'test2' => ['rules' => ''], 'test3' => ['rules' => '']],
            false
        );

        $data = new DataItem(['test' => 'test']);
        $data = $transform->process($data,$context);

        $this->assertEquals(['test1' => 1, 'test2' => 1, 'test3' => 1], $data->getData());
    }
}
