<?php

namespace Oliverde8\Component\PhpEtl\OperationConfig\Extract;

use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;

class CsvExtractConfig extends AbstractOperationConfig implements \Oliverde8\Component\PhpEtl\OperationConfig\OperationConfigInterface
{
    public function __construct(
        public readonly string $delimiter = ';',
        public readonly string $enclosure = '"',
        public readonly string $escape = '\\',
        public readonly string $fileKey = 'file',
        string $flavor = "default",
    ){
        parent::__construct($flavor);
    }

    function validate(bool $constructOnly): void
    {
        if (!in_array($this->enclosure, ["'", '"'], true)) {
            throw new \InvalidArgumentException("Enclosure must be a single or double quote");
        }
    }
}