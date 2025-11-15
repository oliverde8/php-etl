<?php

namespace Oliverde8\Component\PhpEtl;

use Oliverde8\Component\PhpEtl\OperationConfig\OperationConfigInterface;

class ChainConfig implements OperationConfigInterface
{
    /** @var array<OperationConfigInterface> */
    private array $configs;

    /**
     * @param OperationConfigInterface[] $configs
     */
    public function __construct(public readonly int $maxAsynchronousItems = 1)
    {}


    public function addLink(OperationConfigInterface $linkConfig): self {
        $this->configs[] = $linkConfig;
        return $this;
    }

    /**
     * @return array<OperationConfigInterface>
     */
    public function getConfigs(): array
    {
        return $this->configs;
    }

    public function getFlavor(): string
    {
        // Main chain does not have a flavor.
        return "";
    }
}
