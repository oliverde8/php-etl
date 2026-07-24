<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\OperationConfig;

use Oliverde8\Component\PhpEtl\ChainConfig;

class ChainRepeatConfig extends AbstractOperationConfig
{
    /**
     * @param bool $isolateContext When true, the repeated subchain runs against its own clone of the execution
     *                              context, so parameter changes made across repetitions are not visible outside
     *                              this operation.
     */
    public function __construct(
        private readonly ChainConfig $chainConfig,
        public readonly string $validationExpression,
        public readonly bool $allowAsynchronous = false,
        string $flavor = 'default',
        public readonly bool $isolateContext = false,
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

        if (empty($this->validationExpression)) {
            throw new \InvalidArgumentException("Validation expression cannot be empty");
        }
    }
}

