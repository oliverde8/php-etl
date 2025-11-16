<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\OperationConfig\Transformer;

use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;

class LogConfig extends AbstractOperationConfig
{
    public function __construct(
        public readonly string $message,
        public readonly string $level = 'info',
        public readonly array $context = [],
        string $flavor = 'default',
    ) {
        parent::__construct($flavor);
    }

    protected function validate(bool $constructOnly): void
    {
        $validLevels = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
        if (!in_array($this->level, $validLevels, true)) {
            throw new \InvalidArgumentException(
                "Log level must be one of: " . implode(', ', $validLevels) . ". Got: {$this->level}"
            );
        }

        if (empty($this->message)) {
            throw new \InvalidArgumentException("Message cannot be empty");
        }
    }
}

