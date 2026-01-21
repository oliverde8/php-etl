<?php

namespace Oliverde8\Component\PhpEtl\Load\File;

class Json implements FileWriterInterface
{
    /** @var resource File pointer */
    protected $file = null;

    public function __construct(protected string $filePath)
    {
    }

    protected function init()
    {
        if (is_null($this->file)) {
            $this->file = fopen($this->filePath, 'w');
        }
    }

    #[\Override]
    public function getResource()
    {
        $this->init();
        return $this->file;
    }

    #[\Override]
    public function write($rowData)
    {
        $this->init();
        fputs($this->file, json_encode($rowData) . "\n");
    }
}
