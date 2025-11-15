<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\OperationConfig;

use Oliverde8\Component\PhpEtl\Exception\ChainBuilderException;
use Oliverde8\Component\PhpEtl\Exception\ChainBuilderValidationException;

abstract class AbstractOperationConfig implements OperationConfigInterface
{
    private bool $validated = false;

    public function __construct(protected readonly string $flavor)
    {
        $this->validated = true;
        $this->validate();
    }

    /**
     * @throws ChainBuilderValidationException
     */
    abstract protected function validate(): void;

    public function getFlavor(): string
    {
        if (!$this->validated) {
            throw new ChainBuilderException("Impossible to get flavor are you sure the config calls it's parent constructor?");
        }
        return $this->flavor;
    }
}