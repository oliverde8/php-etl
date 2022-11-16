<?php

namespace Oliverde8\Component\PhpEtl\Load\File;

class Json implements FileWriterInterface
{
    protected string $filePath;

    /** @var resource File pointer */
    protected $file = null;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    protected function init()
    {
        if (is_null($this->file)) {
            $this->file = fopen($this->filePath, 'w');
        }
    }

    public function write($rowData)
    {
        $this->init();
        fputs($this->file, json_encode($rowData) . "\n");
    }

    public function getResource()
    {
        return $this->file;
    }
}
