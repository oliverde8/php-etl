<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\OperationConfig;

use Oliverde8\Component\PhpEtl\Exception\ChainBuilderException;
use Oliverde8\Component\PhpEtl\Exception\ChainBuilderValidationException;

abstract class AbstractOperationConfig implements OperationConfigInterface
{
    private bool $constructed = false;

    public function __construct(protected readonly string $flavor = 'default')
    {
        $this->constructed = true;
        $this->validate(true);
    }

    /**
     * @throws ChainBuilderValidationException
     */
    abstract protected function validate(bool $constructOnly): void;

    public function getFlavor(): string
    {
        if (!$this->constructed) {
            throw new ChainBuilderException("Impossible to get flavor are you sure the config calls it's parent constructor?");
        }
        return $this->flavor;
    }
}