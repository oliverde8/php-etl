<?php

namespace Oliverde8\Component\PhpEtl;

use Oliverde8\Component\PhpEtl\OperationConfig\OperationConfigInterface;

class ChainConfig implements OperationConfigInterface
{
    /** @var array<int|string, OperationConfigInterface> */
    private array $configs = [];

    private int $nextIndex = 0;

    /**
     * @param OperationConfigInterface[] $configs
     */
    public function __construct(public readonly int $maxAsynchronousItems = 1)
    {}


    /**
     * @param string|null $name Optional name for this link, used as its identifier in diagrams (e.g. Mermaid) and
     *                           logs/exceptions instead of its numeric position. Defaults to the next numeric index.
     */
    public function addLink(OperationConfigInterface $linkConfig, ?string $name = null): self {
        $key = $name ?? $this->nextIndex++;
        if (array_key_exists($key, $this->configs)) {
            throw new \InvalidArgumentException("A chain link named '$key' already exists.");
        }

        $this->configs[$key] = $linkConfig;
        return $this;
    }

    /**
     * @return array<int|string, OperationConfigInterface>
     */
    public function getConfigs(): array
    {
        return $this->configs;
    }

    #[\Override]
    public function getFlavor(): string
    {
        // Main chain does not have a flavor.
        return "";
    }
}
