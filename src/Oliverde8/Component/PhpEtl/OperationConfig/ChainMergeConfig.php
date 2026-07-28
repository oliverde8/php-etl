<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\OperationConfig;

use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Exception\ChainBuilderException;

class ChainMergeConfig extends AbstractOperationConfig
{
    /** @var array<int|string, ChainConfig> */
    private array $chainConfigs = [];

    private int $nextIndex = 0;

    /**
     * @param string $flavor
     * @param bool $isolateContext When true, each branch runs against its own clone of the execution context,
     *                              so parameter changes made inside a branch are not visible to the other
     *                              branches or to the chain once the merge is done.
     */
    public function __construct(string $flavor = 'default', public readonly bool $isolateContext = false)
    {
        parent::__construct($flavor);
    }

    /**
     * @return array<int|string, ChainConfig>
     */
    public function getChainConfigs(): array
    {
        return $this->chainConfigs;
    }

    /**
     * @param string|null $name Optional name for this branch, used as its identifier in diagrams (e.g. Mermaid)
     *                           and logs/exceptions instead of its numeric position. Defaults to the next numeric index.
     */
    public function addMerge(ChainConfig $chainConfig, ?string $name = null): self
    {
        $key = $name ?? $this->nextIndex++;
        if (array_key_exists($key, $this->chainConfigs)) {
            throw new \InvalidArgumentException("A branch named '$key' already exists.");
        }

        $this->chainConfigs[$key] = $chainConfig;
        return $this;
    }

    #[\Override]
    protected function validate(bool $constructOnly): void
    {
        if ($constructOnly) {
            return;
        }

        if (empty($this->chainConfigs)) {
            throw new ChainBuilderException("At least one chain config must be provided for ChainMergeConfig");
        }
        foreach ($this->chainConfigs as $chainConfig) {
            if (!$chainConfig instanceof ChainConfig) {
                throw new ChainBuilderException("All chain configs must be instances of ChainConfig");
            }
        }
    }
}

