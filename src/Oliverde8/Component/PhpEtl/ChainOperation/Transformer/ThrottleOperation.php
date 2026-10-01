<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\ChainOperation\Transformer;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\DataChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\ThrottleConfig;

class ThrottleOperation extends AbstractChainOperation implements DataChainOperationInterface, ConfigurableChainOperationInterface
{
    private ?int $lastItemAt = null;

    public function __construct(protected readonly ThrottleConfig $config)
    {
    }

    #[\Override]
    public function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        if ($this->lastItemAt !== null) {
            $remaining = $this->lastItemAt + $this->config->intervalMs * 1_000_000 - hrtime(true);
            if ($remaining > 0) {
                usleep(intdiv($remaining, 1000));
            }
        }
        $this->lastItemAt = hrtime(true);

        return $item;
    }
}
