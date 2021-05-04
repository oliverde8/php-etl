<?php
/**
 * File Test.php
 *
 * @author    de Cramer Oliver<oldec@smile.fr>
 * @copyright 2018 Smile
 */

namespace Oliverde8\Component\PhpEtl\Tests\Item;


use Oliverde8\Component\PhpEtl\Item\ChainBreakItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\GroupedItem;
use PHPUnit\Framework\TestCase;

class GetMethodTest extends TestCase
{

    public function testGroupedItem()
    {
        $groupedItem = new GroupedItem(new \ArrayIterator([]));
        $this->assertEquals(DataItemInterface::SIGNAL_DATA, $groupedItem->getMethod());
    }

    public function testChainBreakItem()
    {
        $groupedItem = new ChainBreakItem();
        $this->assertEquals('chainBreak', $groupedItem->getMethod());
    }
}
