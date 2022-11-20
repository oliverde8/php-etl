<?php

namespace Oliverde8\Component\PhpEtl\Item;

class FileLoadedItem extends FileItem
{
    private string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }
}
