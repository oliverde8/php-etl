<?php

namespace Oliverde8\Component\PhpEtl\OperationConfig;

use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Exception\ChainBuilderException;

class ChainSplitConfig extends AbstractOperationConfig
{
    /** @var ChainConfig[] */
    private array $chainConfigs = [];

    /**
     * @param string $flavor
     * @param bool $isolateContext When true, each branch runs against its own clone of the execution context,
     *                              so parameter changes made inside a branch are not visible to the other
     *                              branches or to the chain once the split is done.
     */
    public function __construct(string $flavor = 'default', public readonly bool $isolateContext = false)
    {
        parent::__construct($flavor);
    }

    /**
     * @return ChainConfig[]
     */
    public function getChainConfigs(): array
    {
        return $this->chainConfigs;
    }

    public function addSplit(ChainConfig $chainConfig): self
    {
        $this->chainConfigs[] = $chainConfig;
        return $this;
    }

    #[\Override]
    protected function validate(bool $constructOnly): void
    {
        if ($constructOnly) {
            return;
        }

        if (empty($this->chainConfigs)) {
            throw new ChainBuilderException("At least one chain config must be provided for ChainSplitConfig");
        }
        foreach ($this->chainConfigs as $chainConfig) {
            if (!$chainConfig instanceof ChainConfig) {
                throw new ChainBuilderException("All chain configs must be instances of ChainConfig");
            }
        }
    }
}
