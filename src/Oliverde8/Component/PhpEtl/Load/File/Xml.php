<?php

namespace Oliverde8\Component\PhpEtl\Load\File;

use DOMDocument;
use SimpleXMLElement;

class Xml implements FileWriterInterface
{
    protected string $filePath;
    protected $file = null;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function write($rowData)
    {
        if ($rowData instanceof SimpleXMLElement) {
            $rowData->asXML($this->filePath);
        }

        if (is_string($rowData)) {
            $dom = new DOMDocument;
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($rowData);
            $dom->save($this->filePath);
        }
    }

    public function getResource()
    {
        return $this->file;
    }
}
