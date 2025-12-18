<?php

namespace Oliverde8\Component\PhpEtl\OperationConfig;

use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\Exception\ChainBuilderException;

class ChainSplitConfig extends AbstractOperationConfig
{
    /** @var ChainConfig[] */
    private array $chainConfigs = [];

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
