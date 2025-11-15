<?php

namespace Oliverde8\Component\PhpEtl\OperationConfig\Transformer;

use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;

class CallBackTransformerConfig extends AbstractOperationConfig
{
    private $callable;

    public function __construct(callable $callable, string $flavor = 'default')
    {
        $this->callable = $callable;
        parent::__construct($flavor);
    }

    public function getCallable(): callable
    {
        return $this->callable;
    }

    function validate(): void
    {
        // All callables are valid. Maybe add check on signature in the future.
    }
}