<?php

namespace Oliverde8\Component\PhpEtl\OperationConfig\Loader;

use Oliverde8\Component\PhpEtl\Load\File\Csv;
use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;

class CsvFileWriterConfig extends AbstractOperationConfig implements FileWriterConfigInterface
{
    public function __construct(
        public readonly string $fileName,
        public readonly string $hasHeader = 'true',
        public readonly string $delimiter = ';',
        public readonly string $enclosure = '"',
        public readonly string $escape = '\\',
        public readonly string $fileKey = 'file',
        string $flavor = 'default',
    ){
        parent::__construct($flavor);
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getFile(): Csv
    {
        $tmp = tempnam(sys_get_temp_dir(), 'etl');
        return new Csv($tmp, $this->hasHeader, $this->delimiter, $this->enclosure, $this->escape);
    }

    protected function validate(): void
    {}
}