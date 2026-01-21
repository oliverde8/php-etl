<?php

namespace Oliverde8\Component\PhpEtl\OperationConfig\Loader;

use Oliverde8\Component\PhpEtl\Load\File\Json;
use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;

class JsonFileWriterConfig extends AbstractOperationConfig implements FileWriterConfigInterface
{
    public function __construct(
        public readonly string $fileName,
        string $flavor = 'default',
    ){
        parent::__construct($flavor);
    }

    #[\Override]
    public function getFileName(): string
    {
        return $this->fileName;
    }

    #[\Override]
    public function getFile(): Json
    {
        $tmp = tempnam(sys_get_temp_dir(), 'etl');
        return new Json($tmp);
    }

    #[\Override]
    protected function validate(bool $constructOnly): void
    {}
}