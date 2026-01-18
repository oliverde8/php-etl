<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\OperationConfig;

use Oliverde8\Component\PhpEtl\ChainConfig;

class FailSafeConfig extends AbstractOperationConfig
{
    public function __construct(
        private readonly ChainConfig $chainConfig,
        public readonly array $exceptionsToCatch = [\Exception::class],
        public readonly int $nbAttempts = 3,
        string $flavor = 'default',
    ) {
        parent::__construct($flavor);
    }

    public function getChainConfig(): ChainConfig
    {
        return $this->chainConfig;
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

