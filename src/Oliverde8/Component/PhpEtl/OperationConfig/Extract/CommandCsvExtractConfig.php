<?php

namespace Oliverde8\Component\PhpEtl\OperationConfig\Extract;

use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\OperationConfigInterface;

class CommandCsvExtractConfig extends AbstractOperationConfig implements OperationConfigInterface
{
    /** @var string[][] */
    public readonly array $commands;

    /**
     * @param string[]|string[][] $command
     */
    public function __construct(
        array $command,
        public readonly string $delimiter = ',',
        public readonly string $enclosure = '"',
        public readonly string $escape = '',
        public readonly string $fileKey = 'file',
        public readonly ?array $columns = null,
        public readonly ?float $idleTimeout = null,
        string $flavor = "default",
    ){
        $this->commands = is_array($command[0] ?? null) ? $command : [$command];
        parent::__construct($flavor);
    }

    #[\Override]
    function validate(bool $constructOnly): void
    {
        if (empty($this->commands)) {
            throw new \InvalidArgumentException("Command must not be empty");
        }
        foreach ($this->commands as $command) {
            if (!is_array($command) || empty($command) || !array_is_list($command) || count(array_filter($command, 'is_string')) !== count($command)) {
                throw new \InvalidArgumentException("Each command must be a non empty list of strings");
            }
        }
        if (!in_array($this->enclosure, ["'", '"'], true)) {
            throw new \InvalidArgumentException("Enclosure must be a single or double quote");
        }
        if ($this->idleTimeout !== null && $this->idleTimeout <= 0) {
            throw new \InvalidArgumentException("Idle timeout must be null or greater than 0");
        }
        if ($this->columns === []) {
            throw new \InvalidArgumentException("Columns must be null or a non empty list");
        }
    }
}
