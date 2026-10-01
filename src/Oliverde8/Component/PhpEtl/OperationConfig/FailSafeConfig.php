<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\OperationConfig;

use Oliverde8\Component\PhpEtl\ChainConfig;

class FailSafeConfig extends AbstractOperationConfig
{
    /**
     * @param bool $isolateContext When true, the wrapped subchain runs against its own clone of the execution
     *                              context, so parameter changes made across retry attempts are not visible
     *                              outside this operation.
     * @param ChainConfig|null $onFailure Optional sub-chain receiving the item once all attempts failed with a caught
     *                                     exception, instead of aborting the run. Its output does not continue
     *                                     down the main chain.
     */
    public function __construct(
        private readonly ChainConfig $chainConfig,
        public readonly array $exceptionsToCatch = [\Exception::class],
        public readonly int $nbAttempts = 3,
        string $flavor = 'default',
        public readonly bool $isolateContext = false,
        private readonly ?ChainConfig $onFailure = null,
    ) {
        parent::__construct($flavor);
    }

    public function getChainConfig(): ChainConfig
    {
        return $this->chainConfig;
    }

    public function getOnFailureChainConfig(): ?ChainConfig
    {
        return $this->onFailure;
    }

    #[\Override]
    protected function validate(bool $constructOnly): void
    {
        if ($this->nbAttempts < 1) {
            throw new \InvalidArgumentException('nbAttempts must be >= 1');
        }
        foreach ($this->exceptionsToCatch as $ex) {
            if (!is_string($ex) || (!class_exists($ex) && !interface_exists($ex))) {
                throw new \InvalidArgumentException('exceptionsToCatch must be class/interface names');
            }
        }
    }
}

