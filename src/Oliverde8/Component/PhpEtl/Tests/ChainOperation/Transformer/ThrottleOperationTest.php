<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Tests\ChainOperation\Transformer;

use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\ThrottleOperation;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;
use Oliverde8\Component\PhpEtl\Model\PerExecutionExecutionContext;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\ThrottleConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ThrottleOperationTest extends TestCase
{
    public function testItemsArePaced(): void
    {
        $operation = new ThrottleOperation(new ThrottleConfig(50));
        $context = $this->createContext();

        $start = hrtime(true);
        foreach ([1, 2, 3] as $value) {
            $item = new DataItem(['value' => $value]);
            $this->assertSame($item, $operation->process($item, $context));
        }
        $elapsed = (hrtime(true) - $start) / 1e9;

        $this->assertGreaterThanOrEqual(0.1, $elapsed);
    }

    public function testFirstItemIsNotDelayed(): void
    {
        $operation = new ThrottleOperation(new ThrottleConfig(10000));

        $start = hrtime(true);
        $operation->process(new DataItem([]), $this->createContext());

        $this->assertLessThan(0.5, (hrtime(true) - $start) / 1e9);
    }

    public function testStopItemIsNotDelayed(): void
    {
        $operation = new ThrottleOperation(new ThrottleConfig(10000));
        $context = $this->createContext();
        $operation->process(new DataItem([]), $context);

        $start = hrtime(true);
        $stopItem = new StopItem();
        $this->assertSame($stopItem, $operation->process($stopItem, $context));

        $this->assertLessThan(0.5, (hrtime(true) - $start) / 1e9);
    }

    public function testInvalidRateIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ThrottleConfig(0);
    }

    private function createContext(): PerExecutionExecutionContext
    {
        return new PerExecutionExecutionContext([], $this->createMock(FileSystemInterface::class), new NullLogger(), '/tmp');
    }
}
