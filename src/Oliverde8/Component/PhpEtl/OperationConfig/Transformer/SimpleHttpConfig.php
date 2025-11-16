<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\OperationConfig\Transformer;

use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;

class SimpleHttpConfig extends AbstractOperationConfig
{
    public function __construct(
        public readonly string $method,
        public readonly string $url,
        public readonly bool $responseIsJson = false,
        public readonly ?string $optionKey = null,
        public readonly ?string $responseKey = null,
        string $flavor = 'default',
    ) {
        parent::__construct($flavor);
    }

    protected function validate(bool $constructOnly): void
    {
        $validMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD'];
        if (!in_array($this->method, $validMethods, true)) {
            throw new \InvalidArgumentException(
                "Method must be one of: " . implode(', ', $validMethods) . ". Got: {$this->method}"
            );
        }

        if (empty($this->url)) {
            throw new \InvalidArgumentException("URL cannot be empty");
        }
    }
}

